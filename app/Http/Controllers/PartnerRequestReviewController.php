<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\Vessel;
use App\Services\PartnerRequestApprovalService;
use App\Support\PartnerRequestReviewVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PartnerRequestReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');
        $submissionMethod = $request->get('submission_method', 'all');
        $partnerId = $request->get('partner_id');
        $search = trim((string) $request->get('search'));

        $query = PartnerRequest::query()
            ->with(['partner', 'partnerUser'])
            ->withCount('items');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($submissionMethod !== 'all') {
            $query->where('submission_method', $submissionMethod);
        }

        if (!empty($partnerId)) {
            $query->where('partner_id', $partnerId);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('request_reference', 'like', '%' . $search . '%')
                    ->orWhereHas('partner', function ($partnerQuery) use ($search) {
                        $partnerQuery->where('title', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($status === 'pending') {
            $query->orderBy('submitted_at');
        } else {
            $query->latest('submitted_at');
        }

        $partnerRequests = $query->paginate(20)->appends($request->query());
        $partners = Partner::orderBy('title')->get(['id', 'title']);

        return view('partner-requests.index', compact('partnerRequests', 'partners', 'status', 'submissionMethod', 'partnerId', 'search'));
    }

    public function show(PartnerRequest $partnerRequest)
    {
        $partnerRequest->load([
            'partner',
            'partnerUser',
            'items.vessel',
            'items.driver',
            'trips.driver',
            'trips.crews',
            'approvedBy',
            'declinedBy',
        ]);

        $drivers = Driver::orderBy('name')->get(['id', 'name']);
        $vessels = Vessel::orderBy('name')->get(['id', 'name']);
        $canEdit = $partnerRequest->isPending();
        $requestVersion = PartnerRequestReviewVersion::make($partnerRequest);

        return view('partner-requests.show', compact('partnerRequest', 'drivers', 'vessels', 'canEdit', 'requestVersion'));
    }

    public function update(Request $request, PartnerRequest $partnerRequest)
    {
        $validated = $request->validate([
            'request_version' => ['required', 'string', 'size:64'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer', 'exists:partner_request_items,id'],
            'items.*.trip_date' => ['nullable', 'date'],
            'items.*.pick_up_time' => ['nullable', 'date_format:H:i'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.phone' => ['nullable', 'string', 'max:255'],
            'items.*.phone_2' => ['nullable', 'string', 'max:255'],
            'items.*.address' => ['nullable', 'string', 'max:255'],
            'items.*.from_location' => ['nullable', 'string', 'max:255'],
            'items.*.to_location' => ['nullable', 'string', 'max:255'],
            'items.*.flight_number' => ['nullable', 'string', 'max:255'],
            'items.*.remarks' => ['nullable', 'string'],
            'items.*.sub_remark' => ['nullable', 'string', 'max:255'],
            'items.*.driver_id' => ['nullable', 'exists:drivers,id'],
            'items.*.vessel_id' => ['nullable', 'exists:vessels,id'],
        ]);

        try {
            DB::transaction(function () use ($partnerRequest, $validated) {
                $lockedRequest = PartnerRequest::query()
                    ->whereKey($partnerRequest->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedRequest || !$lockedRequest->isPending()) {
                    throw new \RuntimeException('non_pending');
                }

                $lockedRequest->load('items');

                if (PartnerRequestReviewVersion::isStale($lockedRequest, $validated['request_version'])) {
                    throw new \RuntimeException('stale');
                }

                $this->syncReviewItems($lockedRequest, $validated['items'] ?? []);
                $lockedRequest->touch();
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'stale') {
                return redirect()->route('partner-requests.show', $partnerRequest)
                    ->with('error', 'This request changed while you were reviewing it. Please reload and review the latest version.');
            }

            return redirect()->route('partner-requests.show', $partnerRequest)
                ->with('error', 'Only pending requests can be reviewed.');
        }

        return redirect()->route('partner-requests.show', $partnerRequest)
            ->with('success', 'Review saved successfully.');
    }

    public function approve(Request $request, PartnerRequest $partnerRequest, PartnerRequestApprovalService $approvalService)
    {
        if (!Auth::user()->hasPermission('create_trips')) {
            abort(403);
        }

        $validated = $request->validate([
            'request_version' => ['required', 'string', 'size:64'],
        ]);

        $result = $approvalService->approve(
            $partnerRequest,
            Auth::id(),
            $validated['request_version']
        );

        if (!$result['success']) {
            return redirect()->route('partner-requests.show', $partnerRequest)
                ->with('error', $result['message'])
                ->with('approval_errors', $result['errors'] ?? []);
        }

        return redirect()->route('partner-requests.show', $partnerRequest)
            ->with('success', $result['message']);
    }

    public function decline(Request $request, PartnerRequest $partnerRequest, PartnerRequestApprovalService $approvalService)
    {
        $validated = $request->validate([
            'request_version' => ['required', 'string', 'size:64'],
            'decline_reason' => ['required', 'string', 'max:2000'],
        ]);

        $result = $approvalService->decline(
            $partnerRequest,
            Auth::id(),
            $validated['decline_reason'],
            $validated['request_version']
        );

        if (!$result['success']) {
            return redirect()->route('partner-requests.show', $partnerRequest)
                ->with('error', $result['message']);
        }

        return redirect()->route('partner-requests.show', $partnerRequest)
            ->with('success', $result['message']);
    }

    public function image(PartnerRequest $partnerRequest)
    {
        if (!$partnerRequest->isImage() || empty($partnerRequest->source_image_path)) {
            abort(404);
        }

        $storedPath = $partnerRequest->source_image_path;

        if (!$this->isValidPartnerRequestImagePath($storedPath, $partnerRequest->partner_id)) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($storedPath)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('local')->path($storedPath),
            ['Content-Type' => Storage::disk('local')->mimeType($storedPath)]
        );
    }

    protected function syncReviewItems(PartnerRequest $partnerRequest, array $itemsData): void
    {
        $submittedIds = [];

        foreach ($itemsData as $itemData) {
            $fields = [
                'trip_date' => $itemData['trip_date'] ?? null,
                'pick_up_time' => $itemData['pick_up_time'] ?? null,
                'name' => $itemData['name'] ?? null,
                'phone' => $itemData['phone'] ?? null,
                'phone_2' => $itemData['phone_2'] ?? null,
                'address' => $itemData['address'] ?? null,
                'from_location' => $itemData['from_location'] ?? null,
                'to_location' => $itemData['to_location'] ?? null,
                'flight_number' => $itemData['flight_number'] ?? null,
                'remarks' => $itemData['remarks'] ?? null,
                'sub_remark' => $itemData['sub_remark'] ?? null,
                'driver_id' => $itemData['driver_id'] ?? null,
                'vessel_id' => $itemData['vessel_id'] ?? null,
            ];

            if (!empty($itemData['id'])) {
                $item = PartnerRequestItem::query()
                    ->where('id', $itemData['id'])
                    ->where('partner_request_id', $partnerRequest->id)
                    ->first();

                if ($item) {
                    $item->update($fields);
                    $submittedIds[] = $item->id;
                }

                continue;
            }

            $newItem = $partnerRequest->items()->create($fields);
            $submittedIds[] = $newItem->id;
        }

        $partnerRequest->items()
            ->whereNotIn('id', $submittedIds)
            ->delete();
    }

    protected function isValidPartnerRequestImagePath(string $storedPath, int $partnerId): bool
    {
        if (str_contains($storedPath, '..')) {
            return false;
        }

        $expectedPrefix = 'partner-requests/' . $partnerId . '/';

        return str_starts_with($storedPath, $expectedPrefix);
    }
}
