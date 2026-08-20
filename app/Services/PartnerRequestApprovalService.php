<?php

namespace App\Services;

use App\Models\PartnerRequest;
use App\Support\PartnerRequestReviewVersion;
use Illuminate\Support\Facades\DB;

class PartnerRequestApprovalService
{
    /**
     * @return array{success: bool, message: string}
     */
    public function approve(PartnerRequest $partnerRequest, int $approvedByUserId, string $requestVersion): array
    {
        return DB::transaction(function () use ($partnerRequest, $approvedByUserId, $requestVersion) {
            $lockedRequest = PartnerRequest::query()
                ->whereKey($partnerRequest->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedRequest || !$lockedRequest->isPending()) {
                return [
                    'success' => false,
                    'message' => 'This request has already been processed.',
                ];
            }

            $lockedRequest->load('items');

            if (PartnerRequestReviewVersion::isStale($lockedRequest, $requestVersion)) {
                return [
                    'success' => false,
                    'message' => 'This request changed while you were reviewing it. Please reload and review the latest version.',
                ];
            }

            if ($lockedRequest->trips()->exists()) {
                return [
                    'success' => false,
                    'message' => 'This request has already been processed.',
                ];
            }

            $lockedRequest->update([
                'status' => PartnerRequest::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $approvedByUserId,
            ]);

            return [
                'success' => true,
                'message' => 'Request approved successfully.',
            ];
        });
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function decline(PartnerRequest $partnerRequest, int $declinedByUserId, string $declineReason, string $requestVersion): array
    {
        return DB::transaction(function () use ($partnerRequest, $declinedByUserId, $declineReason, $requestVersion) {
            $lockedRequest = PartnerRequest::query()
                ->whereKey($partnerRequest->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedRequest || !$lockedRequest->isPending()) {
                return [
                    'success' => false,
                    'message' => 'Only pending requests can be declined.',
                ];
            }

            $lockedRequest->load('items');

            if (PartnerRequestReviewVersion::isStale($lockedRequest, $requestVersion)) {
                return [
                    'success' => false,
                    'message' => 'This request changed while you were reviewing it. Please reload and review the latest version.',
                ];
            }

            if ($lockedRequest->trips()->exists()) {
                return [
                    'success' => false,
                    'message' => 'This request cannot be declined because trips already exist.',
                ];
            }

            $lockedRequest->update([
                'status' => PartnerRequest::STATUS_DECLINED,
                'declined_at' => now(),
                'declined_by' => $declinedByUserId,
                'decline_reason' => $declineReason,
            ]);

            return [
                'success' => true,
                'message' => 'Request declined successfully.',
            ];
        });
    }
}
