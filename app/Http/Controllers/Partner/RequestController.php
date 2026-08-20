<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\Vessel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    /**
     * Display a listing of the partner's requests.
     */
    public function index(Request $request)
    {
        $partnerUser = Auth::guard('partner')->user();
        $partnerId = $partnerUser->partner_id;

        $query = PartnerRequest::where('partner_id', $partnerId)
            ->with(['partnerUser', 'items']);

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(15)->appends($request->query());

        return view('partner.requests.index', compact('requests'));
    }

    /**
     * Show the form for creating a new request.
     */
    public function create()
    {
        $partnerUser = Auth::guard('partner')->user();
        $partner = $partnerUser->partner;

        // Check if manual submission is enabled
        if (!$partner->allow_manual_submission) {
            return redirect()->route('partner.requests.index')
                ->with('error', 'Manual request submission is not enabled for your account.');
        }

        // Load vessels for dropdown
        $vessels = Vessel::orderBy('name')->get(['id', 'name']);

        return view('partner.requests.create', compact('vessels'));
    }

    /**
     * Store a newly created request.
     */
    public function store(Request $request)
    {
        $partnerUser = Auth::guard('partner')->user();
        $partner = $partnerUser->partner;

        // Check if manual submission is enabled
        if (!$partner->allow_manual_submission) {
            return redirect()->route('partner.requests.index')
                ->with('error', 'Manual request submission is not enabled for your account.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.trip_date' => ['required', 'date'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.phone' => ['nullable', 'string', 'max:255'],
            'items.*.from_location' => ['required', 'string', 'max:255'],
            'items.*.to_location' => ['required', 'string', 'max:255'],
            'items.*.vessel_id' => ['nullable', 'exists:vessels,id'],
        ]);

        try {
            DB::beginTransaction();

            // Create partner request
            $partnerRequest = PartnerRequest::create([
                'partner_id' => $partnerUser->partner_id,
                'partner_user_id' => $partnerUser->id,
                'submission_method' => PartnerRequest::METHOD_MANUAL,
                'status' => PartnerRequest::STATUS_PENDING,
                'submitted_at' => now(),
                'extraction_status' => null,
                'source_image_path' => null,
            ]);

            // Create request items - only Partner-editable fields
            foreach ($validated['items'] as $itemData) {
                $partnerRequest->items()->create([
                    'trip_date' => $itemData['trip_date'],
                    'name' => $itemData['name'],
                    'phone' => $itemData['phone'] ?? null,
                    'from_location' => $itemData['from_location'],
                    'to_location' => $itemData['to_location'],
                    'vessel_id' => $itemData['vessel_id'] ?? null,
                    // Internal fields remain null for Partner submission
                    'pick_up_time' => null,
                    'phone_2' => null,
                    'address' => null,
                    'flight_number' => null,
                    'remarks' => null,
                    'sub_remark' => null,
                    'vessel_name_raw' => null,
                    'driver_id' => null,
                ]);
            }

            DB::commit();

            return redirect()->route('partner.requests.show', $partnerRequest)
                ->with('success', "Request {$partnerRequest->request_reference} submitted successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'An error occurred while submitting your request. Please try again.');
        }
    }

    /**
     * Display the specified request.
     */
    public function show(PartnerRequest $partnerRequest)
    {
        $partnerUser = Auth::guard('partner')->user();

        // Verify ownership
        if ($partnerRequest->partner_id !== $partnerUser->partner_id) {
            abort(404);
        }

        $partnerRequest->load(['partner', 'partnerUser', 'items.vessel', 'trips']);

        return view('partner.requests.show', compact('partnerRequest'));
    }

    /**
     * Show the form for editing the specified request.
     */
    public function edit(PartnerRequest $partnerRequest)
    {
        $partnerUser = Auth::guard('partner')->user();

        // Verify ownership
        if ($partnerRequest->partner_id !== $partnerUser->partner_id) {
            abort(404);
        }

        // Check if request can be edited
        if (!$partnerRequest->canPartnerEdit()) {
            return redirect()->route('partner.requests.show', $partnerRequest)
                ->with('error', 'This request cannot be edited.');
        }

        $partnerRequest->load('items');

        // Load vessels for dropdown
        $vessels = Vessel::orderBy('name')->get(['id', 'name']);

        return view('partner.requests.edit', compact('partnerRequest', 'vessels'));
    }

    /**
     * Update the specified request.
     */
    public function update(Request $request, PartnerRequest $partnerRequest)
    {
        $partnerUser = Auth::guard('partner')->user();

        // Verify ownership
        if ($partnerRequest->partner_id !== $partnerUser->partner_id) {
            abort(404);
        }

        // Check if request can be edited
        if (!$partnerRequest->canPartnerEdit()) {
            return redirect()->route('partner.requests.show', $partnerRequest)
                ->with('error', 'This request cannot be edited.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'exists:partner_request_items,id'],
            'items.*.trip_date' => ['required', 'date'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.phone' => ['nullable', 'string', 'max:255'],
            'items.*.from_location' => ['required', 'string', 'max:255'],
            'items.*.to_location' => ['required', 'string', 'max:255'],
            'items.*.vessel_id' => ['nullable', 'exists:vessels,id'],
        ]);

        try {
            DB::beginTransaction();

            $submittedItemIds = [];

            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['id'])) {
                    // Update existing item
                    $item = PartnerRequestItem::find($itemData['id']);

                    // Security: verify item belongs to this request
                    if ($item && $item->partner_request_id === $partnerRequest->id) {
                        // Update ONLY Partner-editable fields
                        // Preserve all internal fields: pick_up_time, phone_2, address, 
                        // flight_number, remarks, sub_remark, driver_id, vessel_name_raw
                        $item->update([
                            'trip_date' => $itemData['trip_date'],
                            'name' => $itemData['name'],
                            'phone' => $itemData['phone'] ?? null,
                            'from_location' => $itemData['from_location'],
                            'to_location' => $itemData['to_location'],
                            'vessel_id' => $itemData['vessel_id'] ?? null,
                        ]);

                        $submittedItemIds[] = $item->id;
                    }
                } else {
                    // Create new item - only Partner-editable fields
                    $newItem = $partnerRequest->items()->create([
                        'trip_date' => $itemData['trip_date'],
                        'name' => $itemData['name'],
                        'phone' => $itemData['phone'] ?? null,
                        'from_location' => $itemData['from_location'],
                        'to_location' => $itemData['to_location'],
                        'vessel_id' => $itemData['vessel_id'] ?? null,
                        // Internal fields remain null
                        'pick_up_time' => null,
                        'phone_2' => null,
                        'address' => null,
                        'flight_number' => null,
                        'remarks' => null,
                        'sub_remark' => null,
                        'vessel_name_raw' => null,
                        'driver_id' => null,
                    ]);

                    $submittedItemIds[] = $newItem->id;
                }
            }

            // Delete items that were removed (only from this request)
            $partnerRequest->items()
                ->whereNotIn('id', $submittedItemIds)
                ->delete();

            // Update partner_updated_at timestamp
            $partnerRequest->update([
                'partner_updated_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('partner.requests.show', $partnerRequest)
                ->with('success', 'Request updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'An error occurred while updating your request. Please try again.');
        }
    }

    /**
     * Withdraw the specified request.
     */
    public function withdraw(PartnerRequest $partnerRequest)
    {
        $partnerUser = Auth::guard('partner')->user();

        // Verify ownership
        if ($partnerRequest->partner_id !== $partnerUser->partner_id) {
            abort(404);
        }

        // Only pending requests can be withdrawn
        if (!$partnerRequest->isPending()) {
            return redirect()->route('partner.requests.show', $partnerRequest)
                ->with('error', 'Only pending requests can be withdrawn.');
        }

        $partnerRequest->update([
            'status' => PartnerRequest::STATUS_WITHDRAWN,
            'withdrawn_at' => now(),
        ]);

        return redirect()->route('partner.requests.show', $partnerRequest)
            ->with('success', 'Request withdrawn successfully.');
    }
}
