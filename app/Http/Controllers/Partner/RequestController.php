<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\Vessel;
use App\Services\PartnerRequestImageExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
     * Show the new request method selector or redirect to the only enabled method.
     */
    public function newRequest()
    {
        $partner = Auth::guard('partner')->user()->partner;

        if ($partner->allow_manual_submission && $partner->allow_image_submission) {
            return view('partner.requests.new');
        }

        if ($partner->allow_manual_submission) {
            return redirect()->route('partner.requests.create');
        }

        if ($partner->allow_image_submission) {
            return redirect()->route('partner.requests.image.create');
        }

        return redirect()->route('partner.requests.index')
            ->with('info', 'Request submission is not currently enabled for your account.');
    }

    /**
     * Show the image upload form.
     */
    public function createImage()
    {
        if (! $this->imageSubmissionEnabled()) {
            return redirect()->route('partner.dashboard')
                ->with('error', 'Image request submission is not enabled for your account.');
        }

        return view('partner.requests.upload');
    }

    /**
     * Store an image-based partner request.
     */
    public function storeImage(Request $request, PartnerRequestImageExtractionService $extractionService)
    {
        if (! $this->imageSubmissionEnabled()) {
            return redirect()->route('partner.dashboard')
                ->with('error', 'Image request submission is not enabled for your account.');
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:10240'],
        ]);

        $partnerUser = Auth::guard('partner')->user();
        $storedPath = null;
        $partnerRequest = null;

        try {
            $uploadedFile = $request->file('image');
            $extension = $uploadedFile->guessExtension() ?: 'jpg';
            $filename = Str::uuid()->toString().'.'.$extension;
            $storedPath = $uploadedFile->storeAs(
                'partner-requests/'.$partnerUser->partner_id,
                $filename,
                'local'
            );

            if (! $storedPath || ! Storage::disk('local')->exists($storedPath)) {
                throw new \RuntimeException('Image storage failed.');
            }

            $fullPath = Storage::disk('local')->path($storedPath);

            $partnerRequest = PartnerRequest::create([
                'partner_id' => $partnerUser->partner_id,
                'partner_user_id' => $partnerUser->id,
                'submission_method' => PartnerRequest::METHOD_IMAGE,
                'status' => PartnerRequest::STATUS_PENDING,
                'submitted_at' => now(),
                'source_image_path' => $storedPath,
                'extraction_status' => PartnerRequest::EXTRACTION_PROCESSING,
            ]);

            // Extract and persist items in a transaction
            try {
                DB::transaction(function () use ($partnerRequest, $fullPath, $extractionService) {
                    $extractionResult = $extractionService->extractFromStoredImage(
                        $fullPath,
                        $partnerRequest->request_reference
                    );

                    foreach ($extractionResult['items'] as $itemData) {
                        $partnerRequest->items()->create($itemData);
                    }

                    $partnerRequest->update([
                        'extraction_status' => $extractionResult['status'],
                    ]);
                });
            } catch (\Throwable $e) {
                // Extraction or item persistence failed after REQ exists
                // Mark extraction as failed and preserve the REQ/image
                $partnerRequest->update(['extraction_status' => PartnerRequest::EXTRACTION_FAILED]);

                \Illuminate\Support\Facades\Log::error('Partner request extraction persistence failed', [
                    'request_id' => $partnerRequest->id,
                    'request_reference' => $partnerRequest->request_reference,
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()->route('partner.requests.show', $partnerRequest)
                ->with('success', "Request {$partnerRequest->request_reference} submitted successfully.");
        } catch (\Exception $e) {
            // Image or REQ creation failed
            if ($storedPath && ! $partnerRequest) {
                Storage::disk('local')->delete($storedPath);
            }

            return back()
                ->withInput()
                ->with('error', 'Your image could not be uploaded. Please try again.');
        }
    }

    /**
     * Securely stream the uploaded schedule image for a partner request.
     */
    public function image(PartnerRequest $partnerRequest)
    {
        $this->ensurePartnerOwnsRequest($partnerRequest);

        if (! $partnerRequest->isImage() || empty($partnerRequest->source_image_path)) {
            abort(404);
        }

        $storedPath = $partnerRequest->source_image_path;

        if (! $this->isValidPartnerRequestImagePath($storedPath, $partnerRequest->partner_id)) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($storedPath)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('local')->path($storedPath),
            ['Content-Type' => Storage::disk('local')->mimeType($storedPath)]
        );
    }

    /**
     * Show the form for creating a new request.
     */
    public function create()
    {
        $partnerUser = Auth::guard('partner')->user();
        $partner = $partnerUser->partner;

        // Check if manual submission is enabled
        if (! $partner->allow_manual_submission) {
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
        if (! $partner->allow_manual_submission) {
            return redirect()->route('partner.requests.index')
                ->with('error', 'Manual request submission is not enabled for your account.');
        }

        $validated = $request->validate($this->partnerManualItemValidationRules());

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

            foreach ($validated['items'] as $itemData) {
                $partnerRequest->items()->create(array_merge(
                    $this->partnerEditableItemAttributes($itemData),
                    [
                        'address' => null,
                        'sub_remark' => null,
                        'vessel_name_raw' => null,
                        'driver_id' => null,
                    ]
                ));
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
        if (! $partnerRequest->canPartnerEdit()) {
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
        if (! $partnerRequest->canPartnerEdit()) {
            return redirect()->route('partner.requests.show', $partnerRequest)
                ->with('error', 'This request cannot be edited.');
        }

        $validated = $request->validate(array_merge(
            $this->partnerManualItemValidationRules(),
            [
                'items.*.id' => [
                    'nullable',
                    'integer',
                    Rule::exists('partner_request_items', 'id')
                        ->where('partner_request_id', $partnerRequest->id),
                ],
            ]
        ));

        try {
            return DB::transaction(function () use ($validated, $partnerRequest, $partnerUser) {
                $lockedRequest = PartnerRequest::query()
                    ->whereKey($partnerRequest->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedRequest || $lockedRequest->partner_id !== $partnerUser->partner_id) {
                    abort(404);
                }

                if (! $lockedRequest->canPartnerEdit()) {
                    return redirect()->route('partner.requests.show', $partnerRequest)
                        ->with('error', 'This request cannot be edited.');
                }

                $submittedItemIds = [];

                foreach ($validated['items'] as $itemData) {
                    if (! empty($itemData['id'])) {
                        $item = PartnerRequestItem::find($itemData['id']);

                        if ($item && $item->partner_request_id === $lockedRequest->id) {
                            $item->update($this->partnerEditableItemAttributes($itemData));

                            $submittedItemIds[] = $item->id;
                        }
                    } else {
                        $newItem = $lockedRequest->items()->create(array_merge(
                            $this->partnerEditableItemAttributes($itemData),
                            [
                                'address' => null,
                                'sub_remark' => null,
                                'vessel_name_raw' => null,
                                'driver_id' => null,
                            ]
                        ));

                        $submittedItemIds[] = $newItem->id;
                    }
                }

                $lockedRequest->items()
                    ->whereNotIn('id', $submittedItemIds)
                    ->delete();

                $lockedRequest->update([
                    'partner_updated_at' => now(),
                ]);

                return redirect()->route('partner.requests.show', $lockedRequest)
                    ->with('success', 'Request updated successfully.');
            });
        } catch (\Exception $e) {
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
        if (! $partnerRequest->isPending()) {
            return redirect()->route('partner.requests.show', $partnerRequest)
                ->with('error', 'Only pending requests can be withdrawn.');
        }

        try {
            return DB::transaction(function () use ($partnerRequest, $partnerUser) {
                $lockedRequest = PartnerRequest::query()
                    ->whereKey($partnerRequest->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedRequest || $lockedRequest->partner_id !== $partnerUser->partner_id) {
                    abort(404);
                }

                if (! $lockedRequest->isPending()) {
                    return redirect()->route('partner.requests.show', $partnerRequest)
                        ->with('error', 'Only pending requests can be withdrawn.');
                }

                $lockedRequest->update([
                    'status' => PartnerRequest::STATUS_WITHDRAWN,
                    'withdrawn_at' => now(),
                ]);

                return redirect()->route('partner.requests.show', $lockedRequest)
                    ->with('success', 'Request withdrawn successfully.');
            });
        } catch (\Exception $e) {
            return redirect()->route('partner.requests.show', $partnerRequest)
                ->with('error', 'Unable to withdraw this request. Please try again.');
        }
    }

    protected function imageSubmissionEnabled(): bool
    {
        return (bool) Auth::guard('partner')->user()->partner->allow_image_submission;
    }

    protected function ensurePartnerOwnsRequest(PartnerRequest $partnerRequest): void
    {
        if ($partnerRequest->partner_id !== Auth::guard('partner')->user()->partner_id) {
            abort(404);
        }
    }

    protected function isValidPartnerRequestImagePath(string $storedPath, int $partnerId): bool
    {
        if (str_contains($storedPath, '..')) {
            return false;
        }

        $expectedPrefix = 'partner-requests/'.$partnerId.'/';

        return str_starts_with($storedPath, $expectedPrefix);
    }

    /**
     * @return array<string, mixed>
     */
    protected function partnerManualItemValidationRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.trip_date' => ['required', 'date'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.phone' => ['nullable', 'string', 'max:255'],
            'items.*.phone_2' => ['nullable', 'string', 'max:255'],
            'items.*.from_location' => ['required', 'string', 'max:255'],
            'items.*.to_location' => ['required', 'string', 'max:255'],
            'items.*.vessel_id' => ['nullable', 'exists:vessels,id'],
            'items.*.pick_up_time' => ['nullable', 'date_format:H:i'],
            'items.*.flight_number' => ['nullable', 'string', 'max:255'],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }

    /**
     * @param  array<string, mixed>  $itemData
     * @return array<string, mixed>
     */
    protected function partnerEditableItemAttributes(array $itemData): array
    {
        $pickUpTime = $itemData['pick_up_time'] ?? null;
        if (is_string($pickUpTime) && preg_match('/^\d{2}:\d{2}$/', $pickUpTime)) {
            $pickUpTime .= ':00';
        }

        return [
            'trip_date' => $itemData['trip_date'],
            'name' => $itemData['name'],
            'phone' => $itemData['phone'] ?? null,
            'phone_2' => $itemData['phone_2'] ?? null,
            'from_location' => $itemData['from_location'],
            'to_location' => $itemData['to_location'],
            'vessel_id' => $itemData['vessel_id'] ?? null,
            'pick_up_time' => $pickUpTime,
            'flight_number' => $itemData['flight_number'] ?? null,
            'remarks' => $itemData['remarks'] ?? null,
        ];
    }
}
