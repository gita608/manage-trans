<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\Driver;
use App\Models\Vessel;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Services\TextractService;
use App\Services\TripAssignmentNotificationService;
use App\Services\TripLifecyclePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TripController extends Controller
{
    public function __construct(
        protected TripAssignmentNotificationService $tripAssignmentNotificationService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Trip::with(['driver', 'crews.vessel', 'partner', 'partnerRequest']);

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('trip_reference', 'like', '%' . $search . '%')
                    ->orWhereHas('partnerRequest', function ($partnerRequestQuery) use ($search) {
                        $partnerRequestQuery->where('request_reference', 'like', '%' . $search . '%');
                    });
            });
        }

        // Date Filtering Logic
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $dateRange = $request->input('date_range');

        // Handle preset ranges
        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $dateFrom = $dateTo = today()->format('Y-m-d');
                    break;
                case 'yesterday':
                    $dateFrom = $dateTo = today()->subDay()->format('Y-m-d');
                    break;
                case 'tomorrow':
                    $dateFrom = $dateTo = Carbon::tomorrow()->format('Y-m-d');
                    break;
                case 'last_2_days':
                    $dateFrom = today()->subDay()->format('Y-m-d');
                    $dateTo = today()->format('Y-m-d');
                    break;
                case 'last_7_days':
                    $dateFrom = today()->subDays(6)->format('Y-m-d');
                    $dateTo = today()->format('Y-m-d');
                    break;
                case 'this_month':
                    $dateFrom = today()->startOfMonth()->format('Y-m-d');
                    $dateTo = today()->endOfMonth()->format('Y-m-d');
                    break;
            }
        }

        // Apply date filter
        if ($dateFrom && $dateTo) {
            $query->whereBetween('trip_date', [$dateFrom, $dateTo]);
        } elseif ($request->has('date') && $request->date) {
            // Legacy/Single date support
            $query->whereDate('trip_date', $request->date);
        } else {
            // Default to today if no specific date filter
            if (!$dateFrom && !$dateTo && !$request->has('date')) {
                $query->whereDate('trip_date', today());
            }
        }

        if ($request->has('driver') && $request->driver) {
            $query->whereHas('driver', function ($q) use ($request) {
                $q->where('name', $request->driver);
            });
        }

        if ($request->has('vessel') && $request->vessel) {
            $query->whereHas('crews.vessel', function ($q) use ($request) {
                $q->where('name', $request->vessel);
            });
        }

        // Add status filter (status is now on trips table)
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $trips = $query->latest('created_at')
            ->get();

        // Calculate trip status data for each trip
        $trips = $trips->map(function ($trip) {
            $totalJobs = $trip->crews->count();
            $isCompleted = $trip->isCompleted();
            
            $trip->tripStatus = [
                'totalJobs' => $totalJobs,
                'isCompleted' => $isCompleted,
                'statusBadge' => $trip->getStatusBadge(),
                'statusText' => $trip->getStatusText(),
            ];
            
            return $trip;
        });

        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();

        // Calculate statistics for overview cards based on the filtered trips
        $tripIds = $trips->pluck('id');
        
        // Count trips in progress (trips with at least one crew in progress but not all completed)
        $tripsInProgress = $trips->filter(function ($trip) {
            return $trip->status === TripCrew::STATUS_IN_PROGRESS;
        })->count();
        
        // Count completed trips
        $tripsCompleted = $trips->filter(function ($trip) {
            return $trip->isCompleted();
        })->count();

        // Count cancelled trips
        $tripsCancelled = $trips->filter(function ($trip) {
            return $trip->isCancelled();
        })->count();
        
        $stats = [
            'total_trips' => $trips->count(),
            'total_jobs' => $tripIds->isEmpty() ? 0 : TripCrew::whereIn('trip_id', $tripIds)->count(),
            'trips_in_progress' => $tripsInProgress,
            'trips_completed' => $tripsCompleted,
            'trips_cancelled' => $tripsCancelled,
        ];

        return view('trips.index', compact('trips', 'drivers', 'vessels', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();
        $partners = Partner::orderBy('is_default', 'desc')->orderBy('title')->get();
        $defaultPartner = Partner::where('is_default', true)->first();

        return view('trips.create', compact('drivers', 'vessels', 'partners', 'defaultPartner'));
    }

    public function createFromPartnerRequest(PartnerRequest $partnerRequest)
    {
        if (!$partnerRequest->isApproved()) {
            abort(404);
        }

        if ($partnerRequest->trips()->exists()) {
            return redirect()->route('partner-requests.show', $partnerRequest)
                ->with('error', 'Operational trips have already been created from this request.');
        }

        $partnerRequest->load(['items.vessel', 'partner']);
        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();
        $partners = Partner::whereKey($partnerRequest->partner_id)->orderBy('title')->get();
        $defaultPartner = $partnerRequest->partner;
        $sourcePartnerRequest = $partnerRequest;
        $prefillCrews = $this->buildCrewPrefillFromPartnerRequest($partnerRequest);

        return view('trips.create', compact(
            'drivers',
            'vessels',
            'partners',
            'defaultPartner',
            'sourcePartnerRequest',
            'prefillCrews'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'partner_id' => ['nullable', 'exists:partners,id'],
            'partner_request_id' => ['nullable', 'integer', 'exists:partner_requests,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'crews' => ['required', 'array', 'min:1'],
            'crews.*.driver_id' => ['nullable', 'exists:drivers,id'],
            'crews.*.trip_date' => ['required', 'date'],
            'crews.*.vessel_id' => ['required', 'exists:vessels,id'],
            'crews.*.pick_up_time' => ['required'],
            'crews.*.from_location' => ['required', 'string', 'max:255'],
            'crews.*.to_location' => ['required', 'string', 'max:255'],
            'crews.*.flight_number' => ['nullable', 'string', 'max:255'],
            'crews.*.remarks' => ['nullable', 'string'],
            'crews.*.sub_remark' => ['nullable', 'string'],
            'crews.*.name' => ['required', 'string', 'max:255'],
            'crews.*.phone' => ['nullable', 'string', 'max:255'],
            'crews.*.phone_2' => ['nullable', 'string', 'max:255'],
            'crews.*.address' => ['nullable', 'string'],
        ], [
            'crews.required' => 'At least one crew member row is required.',
            'crews.*.trip_date.required' => 'A trip date is required for every crew row.',
            'crews.*.trip_date.date' => 'Each crew row must have a valid trip date.',
            'crews.*.vessel_id.required' => 'A vessel selection is required for every crew member row.',
            'crews.*.vessel_id.exists' => 'The selected vessel is invalid.',
            'crews.*.pick_up_time.required' => 'Pick-up time is required for every crew member row.',
            'crews.*.from_location.required' => 'From location is required for every crew member row.',
            'crews.*.to_location.required' => 'To location is required for every crew member row.',
            'crews.*.name.required' => 'Crew member name is required for every row.',
        ]);

        $partnerRequestId = $request->input('partner_request_id');
        $sourcePartnerRequest = null;

        if ($partnerRequestId) {
            $sourcePartnerRequest = PartnerRequest::query()->find($partnerRequestId);
            if (!$sourcePartnerRequest || !$sourcePartnerRequest->isApproved()) {
                return back()->withInput()->with('error', 'The source partner request is not approved.');
            }
        }

        $partnerId = $request->input('partner_id');
        if ($sourcePartnerRequest) {
            $partnerId = $sourcePartnerRequest->partner_id;
        } elseif (empty($partnerId) || $partnerId === '') {
            $defaultPartner = Partner::where('is_default', true)->first();
            $partnerId = $defaultPartner ? $defaultPartner->id : null;
        } else {
            $partnerId = (int) $partnerId;
        }

        $rootDriverId = $validated['driver_id'] ?? null;
        $groupedTrips = $this->groupCrewsByDriverAndDate($request->crews, $rootDriverId);
        $tripIdsToNotify = [];

        try {
            DB::transaction(function () use ($groupedTrips, $partnerId, $partnerRequestId, &$tripIdsToNotify) {
                if ($partnerRequestId) {
                    $lockedRequest = PartnerRequest::query()
                        ->whereKey($partnerRequestId)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedRequest || !$lockedRequest->isApproved()) {
                        throw new \RuntimeException('source_not_approved');
                    }

                    if ($lockedRequest->trips()->exists()) {
                        throw new \RuntimeException('already_converted');
                    }

                    $partnerId = $lockedRequest->partner_id;
                }

                foreach ($groupedTrips as $group) {
                    $driverId = $group['driver_id'];
                    $tripDate = $group['trip_date'];
                    $tripTitle = Trip::generateTripTitle($driverId, $tripDate);
                    $status = $driverId ? TripCrew::STATUS_ASSIGNED : TripCrew::STATUS_UNASSIGNED;

                    $trip = Trip::create([
                        'driver_id' => $driverId,
                        'partner_id' => $partnerId,
                        'partner_request_id' => $partnerRequestId,
                        'trip_date' => $tripDate,
                        'title' => $tripTitle,
                        'status' => $status,
                    ]);

                    foreach ($group['crews'] as $crewData) {
                        $trip->crews()->create($crewData);
                    }

                    if ($driverId) {
                        $tripIdsToNotify[] = $trip->id;
                    }
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'already_converted' && $sourcePartnerRequest) {
                return redirect()->route('partner-requests.show', $sourcePartnerRequest)
                    ->with('error', 'Operational trips have already been created from this request.');
            }

            return back()->withInput()->with('error', 'Unable to create trips from this partner request.');
        }

        foreach ($tripIdsToNotify as $tripId) {
            $this->notifyTripAssignment($tripId);
        }

        if ($sourcePartnerRequest) {
            return redirect()->route('partner-requests.show', $sourcePartnerRequest)
                ->with('success', 'Operational trip(s) created successfully from the partner request.');
        }

        return redirect()->route('trips.index')->with('success', 'Trip(s) created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Trip $trip)
    {
        $trip->load(['driver', 'crews.vessel', 'partner', 'partnerRequest', 'activityLogs.user', 'activityLogs.driver', 'tripIssues.issueType', 'tripIssues.driver', 'tripExpenses.expenseType', 'tripExpenses.driver']);
        
        // Calculate trip status data
        $totalJobs = $trip->crews->count();
        $isCompleted = $trip->isCompleted();
        
        $tripStatus = [
            'totalJobs' => $totalJobs,
            'isCompleted' => $isCompleted,
            'statusBadge' => $trip->getStatusBadge(),
            'statusText' => $trip->getStatusText(),
        ];

        $lifecycle = app(TripLifecyclePresenter::class)->present($trip);
        
        return view('trips.show', compact('trip', 'tripStatus', 'lifecycle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Trip $trip)
    {
        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();
        $partners = Partner::orderBy('is_default', 'desc')->orderBy('title')->get();
        $isPartnerSourced = (bool) $trip->partner_request_id;

        return view('trips.edit', compact('trip', 'drivers', 'vessels', 'partners', 'isPartnerSourced'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'partner_id' => ['nullable', 'exists:partners,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'crews' => ['required', 'array', 'min:1'],
            'crews.*.driver_id' => ['nullable', 'exists:drivers,id'],
            'crews.*.trip_date' => ['required', 'date'],
            'crews.*.vessel_id' => ['required', 'exists:vessels,id'],
            'crews.*.pick_up_time' => ['required'],
            'crews.*.from_location' => ['required', 'string', 'max:255'],
            'crews.*.to_location' => ['required', 'string', 'max:255'],
            'crews.*.flight_number' => ['nullable', 'string', 'max:255'],
            'crews.*.remarks' => ['nullable', 'string'],
            'crews.*.sub_remark' => ['nullable', 'string'],
            'crews.*.name' => ['required', 'string', 'max:255'],
            'crews.*.phone' => ['nullable', 'string', 'max:255'],
            'crews.*.phone_2' => ['nullable', 'string', 'max:255'],
            'crews.*.address' => ['nullable', 'string'],
        ], [
            'crews.required' => 'At least one crew member row is required.',
            'crews.*.trip_date.required' => 'A trip date is required for every crew row.',
            'crews.*.trip_date.date' => 'Each crew row must have a valid trip date.',
            'crews.*.vessel_id.required' => 'A vessel selection is required for every crew member row.',
            'crews.*.vessel_id.exists' => 'The selected vessel is invalid.',
            'crews.*.pick_up_time.required' => 'Pick-up time is required for every crew member row.',
            'crews.*.from_location.required' => 'From location is required for every crew member row.',
            'crews.*.to_location.required' => 'To location is required for every crew member row.',
            'crews.*.name.required' => 'Crew member name is required for every row.',
        ]);

        $partnerId = $validated['partner_id'] ?? null;
        $rootDriverId = $validated['driver_id'] ?? null;
        $groupedTrips = $this->groupCrewsByDriverAndDate($request->crews, $rootDriverId);
        $resolvedPartnerId = $this->resolvePartnerIdForTrip($trip, $partnerId);
        $partnerRequestId = $trip->partner_request_id;
        $tripIdsToNotifyAssignment = [];
        $tripIdsToNotifyUpdate = [];

        DB::transaction(function () use ($groupedTrips, $resolvedPartnerId, $partnerRequestId, $trip, &$tripIdsToNotifyAssignment, &$tripIdsToNotifyUpdate) {
            $isFirst = true;
            foreach ($groupedTrips as $group) {
                $driverId = $group['driver_id'];
                $groupTripDate = $group['trip_date'];

                if ($isFirst) {
                    $oldDriverId = $trip->driver_id;
                    $driverChanged = $oldDriverId != $driverId;
                    $driverNewlyAssigned = !$oldDriverId && $driverId;

                    $tripDateFormatted = $trip->trip_date instanceof \Carbon\Carbon
                        ? $trip->trip_date->format('Y-m-d')
                        : Carbon::parse($trip->trip_date)->format('Y-m-d');
                    $dateChanged = $tripDateFormatted !== Carbon::parse($groupTripDate)->format('Y-m-d');

                    $tripTitle = $trip->title;
                    if ($driverChanged || $dateChanged) {
                        $tripTitle = Trip::generateTripTitle($driverId, $groupTripDate, $trip->id);
                    }

                    $updateData = [
                        'driver_id' => $driverId,
                        'partner_id' => $resolvedPartnerId,
                        'trip_date' => $groupTripDate,
                        'title' => $tripTitle,
                    ];

                    if ($driverNewlyAssigned && $trip->status === TripCrew::STATUS_UNASSIGNED) {
                        $updateData['status'] = TripCrew::STATUS_ASSIGNED;
                    } elseif (!$driverId && $trip->status === TripCrew::STATUS_ASSIGNED) {
                        $updateData['status'] = TripCrew::STATUS_UNASSIGNED;
                    }

                    $trip->update($updateData);
                    $trip->crews()->delete();

                    foreach ($group['crews'] as $crewData) {
                        $trip->crews()->create($crewData);
                    }

                    if ($driverId) {
                        if ($driverNewlyAssigned || $driverChanged) {
                            $tripIdsToNotifyAssignment[] = $trip->id;
                        } else {
                            $tripIdsToNotifyUpdate[] = $trip->id;
                        }
                    }

                    $isFirst = false;
                } else {
                    $newTitle = Trip::generateTripTitle($driverId, $groupTripDate);
                    $newStatus = $driverId ? TripCrew::STATUS_ASSIGNED : TripCrew::STATUS_UNASSIGNED;

                    $newTrip = Trip::create([
                        'driver_id' => $driverId,
                        'partner_id' => $resolvedPartnerId,
                        'partner_request_id' => $partnerRequestId,
                        'trip_date' => $groupTripDate,
                        'title' => $newTitle,
                        'status' => $newStatus,
                    ]);

                    foreach ($group['crews'] as $crewData) {
                        $newTrip->crews()->create($crewData);
                    }

                    if ($driverId) {
                        $tripIdsToNotifyAssignment[] = $newTrip->id;
                    }
                }
            }
        });

        foreach (array_unique($tripIdsToNotifyAssignment) as $tripId) {
            $this->notifyTripAssignment($tripId);
        }

        foreach (array_unique($tripIdsToNotifyUpdate) as $tripId) {
            $this->notifyTripUpdated($tripId);
        }

        return redirect()->route('trips.index')->with('success', 'Trip updated successfully!');
    }

    public function assignDriver(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
        ]);

        $driverId = $validated['driver_id'];
        $oldDriverId = $trip->driver_id;
        $driverNewlyAssigned = !$oldDriverId;
        $driverChanged = $oldDriverId != $driverId;

        $tripTitle = Trip::generateTripTitle($driverId, $trip->trip_date, $trip->id);
        $trip->update([
            'driver_id' => $driverId,
            'title' => $tripTitle,
            'status' => $driverNewlyAssigned && $trip->status === TripCrew::STATUS_UNASSIGNED
                ? TripCrew::STATUS_ASSIGNED
                : $trip->status,
        ]);

        if ($driverNewlyAssigned || $driverChanged) {
            $this->notifyTripAssignment($trip->id);
        }

        $driver = Driver::find($driverId);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Driver assigned successfully.',
                'driver_name' => $driver->name,
            ]);
        }

        return redirect()->route('trips.index')->with('success', 'Driver assigned successfully!');
    }

    /**
     * Generate trip title based on driver and date
     */
    public function generateTitle(Request $request)
    {
        $request->validate([
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'trip_date' => ['required', 'date'],
        ]);

        $title = Trip::generateTripTitle(
            $request->driver_id,
            $request->trip_date
        );

        return response()->json(['title' => $title]);
    }

    /**
     * Cancel the specified trip.
     */
    public function cancel(Request $request, Trip $trip)
    {
        $oldStatus = $trip->status;
        $trip->status = TripCrew::STATUS_CANCELLED;
        $trip->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Trip cancelled successfully.',
                'status' => $trip->status,
                'status_badge' => $trip->getStatusBadgeClass(),
                'status_text' => ucfirst($trip->status),
            ]);
        }

        return redirect()->back()->with('success', 'Trip cancelled successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip)
    {
        if ($trip->partner_request_id) {
            return redirect()->back()
                ->with('error', 'Trips created from Partner Requests cannot be deleted. Cancel the trip instead.');
        }

        $trip->delete();

        return redirect()->route('trips.index')->with('success', 'Trip deleted successfully!');
    }

    /**
     * Extract trips from uploaded image using AWS Textract
     */
    public function extractFromImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:10240'], // 10MB max
            'trip_date' => ['nullable', 'date'], // Optional: if not provided, try to extract from image
            'partner_id' => ['nullable', 'exists:partners,id'],
        ]);

        try {
            // Store the uploaded image temporarily
            $uploadedFile = $request->file('image');
            $imagePath = $uploadedFile->store('temp', 'local');
            
            // Get the full absolute path using Storage
            $fullPath = Storage::disk('local')->path($imagePath);

            // Verify file exists
            if (!file_exists($fullPath)) {
                throw new \Exception('Uploaded file could not be saved. Please try again.');
            }

            // Initialize Textract service
            $textractService = new TextractService();
            
            // Extract table data from image
            $tableRows = $textractService->extractTableFromImage($fullPath);

            // Clean up temporary file
            Storage::disk('local')->delete($imagePath);

            if (empty($tableRows)) {
                return redirect()->route('trips.index')
                    ->with('error', 'No table data found in the image. Please ensure the image contains a clear table.');
            }

            // Parse data for review
            $parsedData = $this->parseTableData($tableRows, $request->trip_date);

            if (empty($parsedData)) {
                return redirect()->route('trips.index')
                    ->with('error', 'No valid trip data found in the image.');
            }

            $drivers = Driver::orderBy('name')->get();
            $vessels = Vessel::orderBy('name')->get();
            $partners = Partner::orderBy('is_default', 'desc')->orderBy('title')->get();
            $defaultPartner = Partner::where('is_default', true)->first();
            $selectedPartnerId = $request->input('partner_id') ?: ($defaultPartner ? $defaultPartner->id : null);

            return view('trips.review-extraction', compact('parsedData', 'drivers', 'vessels', 'partners', 'selectedPartnerId'));

        } catch (\Exception $e) {
            return redirect()->route('trips.index')
                ->with('error', 'Failed to extract data from image: ' . $e->getMessage());
        }
    }

    /**
     * Store bulk trips from review page
     */
    public function storeBulk(Request $request)
    {
        $request->validate([
            'trips' => ['required', 'array'],
            'partner_id' => ['nullable', 'exists:partners,id'],
            'trips.*.selected' => ['nullable'], // Checkbox
            'trips.*.driver_id' => ['nullable'], // Can be "new:Name" or ID
            'trips.*.vessel_id' => ['nullable'], // Can be "new:Name" or ID
            'trips.*.trip_date' => ['required', 'date'],
            'trips.*.pick_up_time' => ['required'],
            'trips.*.flight_number' => ['nullable', 'string'],
            'trips.*.crew_name' => ['required', 'string'],
            'trips.*.crew_phone' => ['nullable', 'string', 'max:255'],
            'trips.*.crew_phone_2' => ['nullable', 'string', 'max:255'],
            'trips.*.from_location' => ['required', 'string'],
            'trips.*.to_location' => ['required', 'string'],
            'trips.*.remarks' => ['nullable', 'string'],
            'trips.*.sub_remark' => ['nullable', 'string'],
        ], [
            'trips.required' => 'No trip rows were provided.',
            'trips.*.trip_date.required' => 'A trip date is required for every crew row.',
            'trips.*.trip_date.date' => 'Each crew row must have a valid trip date.',
            'trips.*.pick_up_time.required' => 'Pick-up time is required for all selected trips.',
            'trips.*.crew_name.required' => 'Crew name is required for all selected trips.',
            'trips.*.from_location.required' => 'From location is required for all selected trips.',
            'trips.*.to_location.required' => 'To location is required for all selected trips.',
        ]);

        $createdCount = 0;

        // Group items by Driver + Date to create consolidated trips
        $groupedTrips = [];

        foreach ($request->trips as $index => $tripData) {
            if (!isset($tripData['selected'])) {
                continue;
            }

            $driverId = $tripData['driver_id'] ?? null;

            $vesselId = null;
            if (!empty($tripData['vessel_id'])) {
                if (str_starts_with($tripData['vessel_id'], 'new:')) {
                    continue;
                } else {
                    $vessel = Vessel::find($tripData['vessel_id']);
                    if ($vessel) {
                        $vesselId = $tripData['vessel_id'];
                    } else {
                        continue;
                    }
                }
            } else {
                continue;
            }

            $date = $tripData['trip_date'];
            $key = ($driverId ?: 'unassigned') . '|' . $date;

            if (!isset($groupedTrips[$key])) {
                $groupedTrips[$key] = [
                    'driver_id' => $driverId,
                    'trip_date' => $date,
                    'crews' => []
                ];
            }

            $groupedTrips[$key]['crews'][] = [
                'vessel_id' => $vesselId,
                'pick_up_time' => $tripData['pick_up_time'],
                'flight_number' => $tripData['flight_number'] ?? null,
                'name' => $tripData['crew_name'],
                'phone' => $tripData['crew_phone'] ?? null,
                'phone_2' => $tripData['crew_phone_2'] ?? null,
                'from_location' => $tripData['from_location'],
                'to_location' => $tripData['to_location'],
                'remarks' => $tripData['remarks'] ?? null,
                'sub_remark' => $tripData['sub_remark'] ?? null,
            ];
        }

        $partnerId = $request->input('partner_id');
        if (empty($partnerId)) {
            $defaultPartner = Partner::where('is_default', true)->first();
            $partnerId = $defaultPartner ? $defaultPartner->id : null;
        } else {
            $partnerId = (int) $partnerId;
        }

        $tripIdsToNotify = [];

        DB::transaction(function () use ($groupedTrips, $partnerId, &$createdCount, &$tripIdsToNotify) {
            foreach ($groupedTrips as $group) {
                $driverId = $group['driver_id'] ?: null;
                $title = Trip::generateTripTitle($driverId, $group['trip_date']);
                $status = $driverId ? TripCrew::STATUS_ASSIGNED : TripCrew::STATUS_UNASSIGNED;

                $trip = Trip::create([
                    'driver_id' => $driverId,
                    'partner_id' => $partnerId,
                    'trip_date' => $group['trip_date'],
                    'title' => $title,
                    'status' => $status,
                ]);

                foreach ($group['crews'] as $crewData) {
                    $trip->crews()->create($crewData);
                }

                if ($driverId) {
                    $tripIdsToNotify[] = $trip->id;
                }

                $createdCount++;
            }
        });

        foreach ($tripIdsToNotify as $tripId) {
            $this->notifyTripAssignment($tripId);
        }

        if ($createdCount > 0) {
            return redirect()->route('trips.index')->with('success', "Successfully created {$createdCount} trip(s).");
        }

        return redirect()->route('trips.index')->with('warning', 'No trips were selected or created.');
    }

    /**
     * Group submitted crew rows by driver_id + trip_date.
     * Strips driver_id and trip_date from crew payloads before returning.
     */
    protected function groupCrewsByDriverAndDate(array $crews, $rootDriverId = null): array
    {
        $groupedTrips = [];

        foreach ($crews as $crewData) {
            $driverId = !empty($crewData['driver_id']) ? $crewData['driver_id'] : $rootDriverId;
            $tripDate = $crewData['trip_date'];
            $key = ($driverId ?: 'unassigned') . '|' . $tripDate;

            if (!isset($groupedTrips[$key])) {
                $groupedTrips[$key] = [
                    'driver_id' => $driverId ?: null,
                    'trip_date' => $tripDate,
                    'crews' => [],
                ];
            }

            unset($crewData['driver_id'], $crewData['trip_date']);
            $groupedTrips[$key]['crews'][] = $crewData;
        }

        return $groupedTrips;
    }

    /**
     * Parse table data from Textract results
     */
    protected function parseTableData(array $tableRows, $defaultDate = null)
    {
        $parsedTrips = [];
        $tripDate = $defaultDate ? Carbon::parse($defaultDate) : Carbon::today();

        if (empty($tableRows)) {
            return [];
        }

        // Try to identify header row and date
        $headerRowIndex = 0;
        $dataStartIndex = 1;
        
        if (!empty($tableRows[0])) {
            $firstRowText = implode(' ', array_map('trim', $tableRows[0]));
            if (preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/', $firstRowText, $matches)) {
                try {
                    $parsedDate = Carbon::createFromFormat('d F Y', $matches[1] . ' ' . $matches[2] . ' ' . $matches[3]);
                    $tripDate = $parsedDate;
                    $headerRowIndex = 0;
                    $dataStartIndex = 1;
                } catch (\Exception $e) {
                    // Keep default date
                }
            }
        }

        for ($i = $dataStartIndex; $i < count($tableRows); $i++) {
            $row = $tableRows[$i];
            
            if (count($row) < 3) continue;

            $crewName = trim($row[0] ?? '');
            // $driverName = trim($row[1] ?? ''); // Driver is now manually selected
            $vesselName = trim($row[2] ?? '');
            $pickUpTime = trim($row[3] ?? '');
            $fromLocation = trim($row[4] ?? '');
            $toLocation = trim($row[5] ?? '');
            $followUp = trim($row[6] ?? '');
            $crewPhone = trim($row[7] ?? ''); // Extract phone from column 7

            if ($this->isHeaderRow($row)) continue;
            // if (empty($crewName) && empty($driverName) && empty($vesselName)) continue;
            if (empty($crewName) && empty($vesselName)) continue;

            // Try to extract phone from crew name if it contains "Mobile no." or similar patterns
            if (empty($crewPhone) && !empty($crewName)) {
                // Pattern: "Name - Mobile no.- 0505592732" or "Name Mobile: 0505592732"
                if (preg_match('/(?:Mobile\s*(?:no\.?|number)?[:\-]?\s*)(\d+)/i', $crewName, $matches)) {
                    $crewPhone = $matches[1];
                    // Remove phone from crew name
                    $crewName = preg_replace('/\s*-\s*Mobile\s*(?:no\.?|number)?[:\-]?\s*\d+/i', '', $crewName);
                    $crewName = trim($crewName);
                }
            }

            // Find vessel match - only exact match (case-insensitive)
            // Do NOT auto-create vessels, only match existing ones
            $vessel = Vessel::whereRaw('LOWER(name) = ?', [strtolower($vesselName)])->first();
            // If no exact match, try partial match
            if (!$vessel) {
                $vessel = Vessel::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($vesselName) . '%'])->first();
            }

            $parsedTime = $this->parsePickUpTime($pickUpTime);

            $parsedTrips[] = [
                'trip_date' => $tripDate->format('Y-m-d'),
                'pick_up_time' => $parsedTime,
                'driver_name' => null, // Force manual selection
                'driver_id' => null,
                'vessel_name' => $vesselName,
                'vessel_id' => $vessel ? $vessel->id : null, // Only set if exact match found, otherwise null
                'crew_name' => $crewName,
                'crew_phone' => $crewPhone,
                'crew_phone_2' => trim($row[8] ?? ''),
                'from_location' => $fromLocation ?: 'N/A',
                'to_location' => $toLocation ?: 'N/A',
                'remarks' => $followUp,
                'sub_remark' => null,
            ];
        }

        return $parsedTrips;
    }

    /**
     * Check if a row is a header row by looking for common header patterns
     *
     * @param array $row
     * @return bool
     */
    protected function isHeaderRow(array $row): bool
    {
        // Common header patterns (case-insensitive)
        $headerPatterns = [
            'crew name',
            'driver name',
            'vessel name',
            'pick-up time',
            'pick up time',
            'pickup time',
            'from',
            'to',
            'follow up',
            'followup',
            'confirm action',
            'confirm actioin', // Handle typo in image
            'action',
        ];

        // Check if row contains multiple header-like terms
        $rowText = implode(' ', array_map('strtolower', array_map('trim', $row)));
        $matches = 0;

        foreach ($headerPatterns as $pattern) {
            if (stripos($rowText, $pattern) !== false) {
                $matches++;
            }
        }

        // If 3 or more header patterns match, it's likely a header row
        if ($matches >= 3) {
            return true;
        }

        // Also check if first few columns match header patterns exactly
        if (count($row) >= 3) {
            $firstCol = strtolower(trim($row[0] ?? ''));
            $secondCol = strtolower(trim($row[1] ?? ''));
            $thirdCol = strtolower(trim($row[2] ?? ''));

            $headerColumns = [
                'crew name' => ['crew name'],
                'driver name' => ['driver name'],
                'vessel name' => ['vessel name'],
            ];

            // Check if first 3 columns match header column names
            if (
                (stripos($firstCol, 'crew') !== false || stripos($firstCol, 'name') !== false) &&
                (stripos($secondCol, 'driver') !== false || stripos($secondCol, 'name') !== false) &&
                (stripos($thirdCol, 'vessel') !== false || stripos($thirdCol, 'name') !== false)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse pick-up time from various formats
     * Examples: "0300PM", "3:00 PM", "15:00"
     */
    protected function parsePickUpTime($timeString)
    {
        $timeString = trim($timeString);
        
        // Format: "0300PM" or "0300 PM"
        if (preg_match('/(\d{1,2})(\d{2})(AM|PM)/i', $timeString, $matches)) {
            $hour = (int)$matches[1];
            $minute = (int)$matches[2];
            $meridian = strtoupper($matches[3]);
            
            if ($meridian === 'PM' && $hour < 12) {
                $hour += 12;
            } elseif ($meridian === 'AM' && $hour === 12) {
                $hour = 0;
            }
            
            return sprintf('%02d:%02d', $hour, $minute);
        }
        
        // Format: "3:00 PM" or "15:00"
        try {
            $carbon = Carbon::createFromFormat('g:i A', $timeString);
            return $carbon->format('H:i');
        } catch (\Exception $e) {
            try {
                $carbon = Carbon::createFromFormat('H:i', $timeString);
                return $carbon->format('H:i');
            } catch (\Exception $e2) {
                // Default to current time if parsing fails
                return Carbon::now()->format('H:i');
            }
        }
    }

    protected function resolvePartnerIdForTrip(Trip $trip, ?int $requestedPartnerId): ?int
    {
        if ($trip->partner_request_id) {
            $trip->loadMissing('partnerRequest');

            return $trip->partnerRequest?->partner_id ?? $trip->partner_id;
        }

        return $requestedPartnerId;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildCrewPrefillFromPartnerRequest(PartnerRequest $partnerRequest): array
    {
        if ($partnerRequest->items->isEmpty()) {
            return [[
                'name' => '',
                'driver_id' => '',
                'trip_date' => '',
                'vessel_id' => '',
                'pick_up_time' => '',
                'from_location' => '',
                'to_location' => '',
                'phone' => '',
                'phone_2' => '',
                'remarks' => '',
                'sub_remark' => '',
                'address' => '',
                'flight_number' => '',
            ]];
        }

        return $partnerRequest->items->map(function ($item) use ($partnerRequest) {
            $crew = [
                'trip_date' => $item->trip_date?->format('Y-m-d') ?? '',
                'name' => $item->name ?? '',
                'phone' => $item->phone ?? '',
                'from_location' => $item->from_location ?? '',
                'to_location' => $item->to_location ?? '',
                'vessel_id' => $item->vessel_id ?? '',
                'driver_id' => '',
                'pick_up_time' => '',
                'phone_2' => '',
                'address' => '',
                'flight_number' => '',
                'remarks' => '',
                'sub_remark' => '',
            ];

            if ($partnerRequest->isImage()) {
                $crew['pick_up_time'] = $item->pick_up_time
                    ? Carbon::parse($item->pick_up_time)->format('H:i')
                    : '';
                $crew['phone_2'] = $item->phone_2 ?? '';
                $crew['address'] = $item->address ?? '';
                $crew['flight_number'] = $item->flight_number ?? '';
                $crew['remarks'] = $item->remarks ?? '';
                $crew['sub_remark'] = $item->sub_remark ?? '';
            }

            return $crew;
        })->all();
    }

    protected function notifyTripAssignment(int $tripId): void
    {
        $trip = Trip::with(['driver', 'crews'])->find($tripId);

        if ($trip) {
            $this->tripAssignmentNotificationService->notifyDriverAssigned($trip, Auth::id());
        }
    }

    protected function notifyTripUpdated(int $tripId): void
    {
        $trip = Trip::with(['driver', 'crews'])->find($tripId);

        if ($trip) {
            $this->tripAssignmentNotificationService->notifyDriverTripUpdated($trip, Auth::id());
        }
    }
}

