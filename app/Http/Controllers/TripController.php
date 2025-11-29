<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\Driver;
use App\Models\Vessel;
use App\Services\TextractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Trip::with(['driver', 'crews.vessel']);

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

        // Add status filter
        if ($request->has('status') && $request->status) {
            $query->whereHas('crews', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        $trips = $query->latest('created_at')
            ->get();

        // Calculate trip status data for each trip
        $trips = $trips->map(function ($trip) {
            $totalJobs = $trip->crews->count();
            $isCompleted = $trip->isCompleted();
            $completedJobs = $trip->getCompletedCrewsCount();
            $inProgressJobs = $trip->getInProgressCrewsCount();
            $progressPercent = $totalJobs > 0 ? ($completedJobs / $totalJobs) * 100 : 0;
            
            $trip->tripStatus = [
                'totalJobs' => $totalJobs,
                'isCompleted' => $isCompleted,
                'completedJobs' => $completedJobs,
                'inProgressJobs' => $inProgressJobs,
                'progressPercent' => $progressPercent,
                'statusBadge' => $trip->getStatusBadge(),
                'statusText' => $trip->getStatusText(),
            ];
            
            return $trip;
        });

        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();

        // Calculate statistics for overview cards based on the filtered trips
        $tripIds = $trips->pluck('id');
        
        $stats = [
            'total_trips' => $trips->count(),
            'total_jobs' => $tripIds->isEmpty() ? 0 : TripCrew::whereIn('trip_id', $tripIds)->count(),
            'jobs_in_progress' => $tripIds->isEmpty() ? 0 : TripCrew::whereIn('trip_id', $tripIds)
                ->where('status', TripCrew::STATUS_IN_PROGRESS)->count(),
            'jobs_completed' => $tripIds->isEmpty() ? 0 : TripCrew::whereIn('trip_id', $tripIds)
                ->where('status', TripCrew::STATUS_COMPLETED)->count(),
        ];

        return view('trips.index', compact('trips', 'drivers', 'vessels', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get(); // Still needed for dynamic select
        return view('trips.create', compact('drivers', 'vessels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
            'trip_date' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'crews' => ['required', 'array', 'min:1'],
            'crews.*.vessel_id' => ['required', 'exists:vessels,id'],
            'crews.*.pick_up_time' => ['required'],
            'crews.*.from_location' => ['required', 'string', 'max:255'],
            'crews.*.to_location' => ['required', 'string', 'max:255'],
            'crews.*.flight_number' => ['nullable', 'string', 'max:255'],
            'crews.*.remarks' => ['nullable', 'string'],
            'crews.*.name' => ['required', 'string', 'max:255'],
            'crews.*.phone' => ['nullable', 'string', 'max:255'],
            'crews.*.address' => ['nullable', 'string'],
        ]);

        // Auto-generate trip title based on driver and date
        $tripTitle = Trip::generateTripTitle($validated['driver_id'], $validated['trip_date']);

        $trip = Trip::create([
            'driver_id' => $validated['driver_id'],
            'trip_date' => $validated['trip_date'],
            'title' => $tripTitle,
        ]);

        foreach ($request->crews as $crewData) {
            $trip->crews()->create(array_merge($crewData, ['status' => 'assigned']));
        }

        return redirect()->route('trips.index')->with('success', 'Trip created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Trip $trip)
    {
        $trip->load(['driver', 'crews.vessel', 'activityLogs.user', 'activityLogs.driver', 'tripIssues.issueType', 'tripIssues.driver', 'tripExpenses.expenseType', 'tripExpenses.driver']);
        
        // Calculate trip status data
        $totalJobs = $trip->crews->count();
        $isCompleted = $trip->isCompleted();
        $completedJobs = $trip->getCompletedCrewsCount();
        $inProgressJobs = $trip->getInProgressCrewsCount();
        
        $tripStatus = [
            'totalJobs' => $totalJobs,
            'isCompleted' => $isCompleted,
            'completedJobs' => $completedJobs,
            'inProgressJobs' => $inProgressJobs,
            'statusBadge' => $trip->getStatusBadge(),
            'statusText' => $trip->getStatusText(),
        ];
        
        return view('trips.show', compact('trip', 'tripStatus'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Trip $trip)
    {
        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();
        return view('trips.edit', compact('trip', 'drivers', 'vessels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
            'trip_date' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'crews' => ['required', 'array', 'min:1'],
            'crews.*.vessel_id' => ['required', 'exists:vessels,id'],
            'crews.*.pick_up_time' => ['required'],
            'crews.*.from_location' => ['required', 'string', 'max:255'],
            'crews.*.to_location' => ['required', 'string', 'max:255'],
            'crews.*.flight_number' => ['nullable', 'string', 'max:255'],
            'crews.*.remarks' => ['nullable', 'string'],
            'crews.*.name' => ['required', 'string', 'max:255'],
            'crews.*.phone' => ['nullable', 'string', 'max:255'],
            'crews.*.address' => ['nullable', 'string'],
        ]);

        // Auto-generate trip title if driver or date changed
        $driverChanged = $trip->driver_id != $validated['driver_id'];
        $tripDateFormatted = $trip->trip_date instanceof \Carbon\Carbon 
            ? $trip->trip_date->format('Y-m-d') 
            : Carbon::parse($trip->trip_date)->format('Y-m-d');
        $dateChanged = $tripDateFormatted !== Carbon::parse($validated['trip_date'])->format('Y-m-d');
        
        $tripTitle = $trip->title;
        if ($driverChanged || $dateChanged) {
            // Regenerate title for new driver/date combination
            $tripTitle = Trip::generateTripTitle($validated['driver_id'], $validated['trip_date'], $trip->id);
        }

        $trip->update([
            'driver_id' => $validated['driver_id'],
            'trip_date' => $validated['trip_date'],
            'title' => $tripTitle,
        ]);

        // Sync crews: delete all and recreate
        // Note: In a real app, we might want to update existing IDs to preserve history/logs linked to specific crews
        // But for this refactor, full sync is simpler.
        $trip->crews()->delete();
        
        foreach ($request->crews as $crewData) {
            // Preserve status if we were editing specific crew status (not implemented in edit form yet, so default to assigned)
            $trip->crews()->create(array_merge($crewData, ['status' => 'assigned']));
        }

        return redirect()->route('trips.index')->with('success', 'Trip updated successfully!');
    }

    /**
     * Generate trip title based on driver and date
     */
    public function generateTitle(Request $request)
    {
        $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
            'trip_date' => ['required', 'date'],
        ]);

        $title = Trip::generateTripTitle(
            $request->driver_id,
            $request->trip_date
        );

        return response()->json(['title' => $title]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip)
    {
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

            return view('trips.review-extraction', compact('parsedData', 'drivers', 'vessels'));

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
            'trips.*.selected' => ['nullable'], // Checkbox
            'trips.*.driver_id' => ['nullable'], // Can be "new:Name" or ID
            'trips.*.vessel_id' => ['nullable'], // Can be "new:Name" or ID
            'trips.*.trip_date' => ['required', 'date'],
            'trips.*.pick_up_time' => ['required'],
            'trips.*.flight_number' => ['nullable', 'string'],
            'trips.*.crew_name' => ['required', 'string'],
            'trips.*.from_location' => ['required', 'string'],
            'trips.*.to_location' => ['required', 'string'],
        ]);

        $createdCount = 0;
        
        // Group items by Driver + Date to create consolidated trips
        $groupedTrips = [];

        foreach ($request->trips as $index => $tripData) {
            // Skip if not selected
            if (!isset($tripData['selected'])) {
                continue;
            }

            // Resolve Driver
            $driverId = $tripData['driver_id'] ?? null;
            
            if (!$driverId) {
                continue; // Skip if no driver selected
            }

            // Resolve Vessel
            $vesselId = null;
            if (!empty($tripData['vessel_id'])) {
                if (str_starts_with($tripData['vessel_id'], 'new:')) {
                    $newVesselName = substr($tripData['vessel_id'], 4);
                    $vessel = Vessel::firstOrCreate(['name' => $newVesselName]);
                    $vesselId = $vessel->id;
                } else {
                    $vesselId = $tripData['vessel_id'];
                }
            } else {
                continue; // Skip if no vessel
            }

            $date = $tripData['trip_date'];
            $key = $driverId . '_' . $date;

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
                'from_location' => $tripData['from_location'],
                'to_location' => $tripData['to_location'],
                'remarks' => $tripData['remarks'] ?? null,
                'status' => 'assigned'
            ];
        }

        // Create Trips and Crews
        foreach ($groupedTrips as $group) {
            // Generate title
            $title = Trip::generateTripTitle($group['driver_id'], $group['trip_date']);

            $trip = Trip::create([
                'driver_id' => $group['driver_id'],
                'trip_date' => $group['trip_date'],
                'title' => $title,
            ]);

            foreach ($group['crews'] as $crewData) {
                $trip->crews()->create($crewData);
            }
            
            $createdCount++;
        }

        if ($createdCount > 0) {
            return redirect()->route('trips.index')->with('success', "Successfully created {$createdCount} trip(s).");
        }

        return redirect()->route('trips.index')->with('warning', 'No trips were selected or created.');
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

            if ($this->isHeaderRow($row)) continue;
            // if (empty($crewName) && empty($driverName) && empty($vesselName)) continue;
            if (empty($crewName) && empty($vesselName)) continue;

            // Find vessel match
            $vessel = Vessel::whereRaw('LOWER(name) = ?', [strtolower($vesselName)])->first();
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
                'vessel_id' => $vessel ? $vessel->id : null,
                'crew_name' => $crewName,
                'from_location' => $fromLocation ?: 'N/A',
                'to_location' => $toLocation ?: 'N/A',
                'remarks' => $followUp,
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
}
