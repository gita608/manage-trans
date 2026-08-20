<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PartnerRequest;
use App\Services\PartnerRequestApprovalService;
use App\Support\PartnerRequestReviewVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PartnerRequestReviewController extends Controller
{
    public function index(Request $request)
    {
        return view('partner-requests.index', $this->queueViewData($request, true));
    }

    public function pendingCount()
    {
        return response()->json([
            'pending_count' => $this->pendingRequestCount(),
        ]);
    }

    public function live(Request $request)
    {
        return view('partner-requests.partials.queue-live', $this->queueViewData($request, false));
    }

    public function show(PartnerRequest $partnerRequest)
    {
        $partnerRequest->load([
            'partner',
            'partnerUser',
            'items.vessel',
            'trips.driver',
            'trips.crews',
            'approvedBy',
            'declinedBy',
        ]);

        $canDecide = $partnerRequest->isPending();
        $canCreateTrip = $partnerRequest->isApproved()
            && $partnerRequest->trips->isEmpty()
            && Auth::user()->hasPermission('create_trips');
        $requestVersion = PartnerRequestReviewVersion::make($partnerRequest);

        return view('partner-requests.show', compact(
            'partnerRequest',
            'canDecide',
            'canCreateTrip',
            'requestVersion'
        ));
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
                ->with('error', $result['message']);
        }

        return redirect()->route('trips.create-from-partner-request', $partnerRequest)
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

    protected function queueViewData(Request $request, bool $includePartners): array
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

        $data = [
            'partnerRequests' => $query->paginate(20)->appends($request->query()),
            'status' => $status,
            'submissionMethod' => $submissionMethod,
            'partnerId' => $partnerId,
            'search' => $search,
            'pendingCount' => $this->pendingRequestCount(),
        ];

        if ($includePartners) {
            $data['partners'] = Partner::orderBy('title')->get(['id', 'title']);
        }

        return $data;
    }

    protected function pendingRequestCount(): int
    {
        return PartnerRequest::query()
            ->where('status', PartnerRequest::STATUS_PENDING)
            ->count();
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
