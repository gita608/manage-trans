<?php

namespace App\Services;

use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Support\PartnerRequestReviewVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PartnerRequestApprovalService
{
    /**
     * @return array{success: bool, message: string, errors?: array<int, string>, trips?: array<int, Trip>}
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

            $items = $lockedRequest->items()->get();
            $validationErrors = $this->validateItemsForApproval($items);

            if (!empty($validationErrors)) {
                return [
                    'success' => false,
                    'message' => 'Please complete all required fields before approval.',
                    'errors' => $validationErrors,
                ];
            }

            $createdTrips = [];
            $groupedItems = $this->groupItemsByDriverAndDate($items);

            foreach ($groupedItems as $group) {
                $driverId = $group['driver_id'];
                $tripDate = $group['trip_date'];
                $status = $driverId ? TripCrew::STATUS_ASSIGNED : TripCrew::STATUS_UNASSIGNED;

                $trip = Trip::create([
                    'driver_id' => $driverId,
                    'partner_id' => $lockedRequest->partner_id,
                    'partner_request_id' => $lockedRequest->id,
                    'trip_date' => $tripDate,
                    'title' => Trip::generateTripTitle($driverId, $tripDate),
                    'status' => $status,
                ]);

                foreach ($group['items'] as $item) {
                    $trip->crews()->create($this->mapItemToCrewData($item));
                }

                $createdTrips[] = $trip->fresh(['crews']);
            }

            $lockedRequest->update([
                'status' => PartnerRequest::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $approvedByUserId,
            ]);

            return [
                'success' => true,
                'message' => 'Request approved and operational trips created successfully.',
                'trips' => $createdTrips,
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

    /**
     * @return array<int, string>
     */
    public function validateItemsForApproval(Collection $items): array
    {
        $errors = [];

        if ($items->isEmpty()) {
            $errors[] = 'At least one crew item is required before approval.';

            return $errors;
        }

        foreach ($items as $index => $item) {
            $row = $index + 1;

            if (empty($item->trip_date)) {
                $errors[] = "Crew #{$row}: Trip date is required.";
            }

            if (empty($item->pick_up_time)) {
                $errors[] = "Crew #{$row}: Pick-up time is required.";
            }

            if (trim((string) $item->name) === '') {
                $errors[] = "Crew #{$row}: Crew name is required.";
            }

            if (trim((string) $item->from_location) === '') {
                $errors[] = "Crew #{$row}: From location is required.";
            }

            if (trim((string) $item->to_location) === '') {
                $errors[] = "Crew #{$row}: To location is required.";
            }

            if (empty($item->vessel_id)) {
                $errors[] = "Crew #{$row}: Vessel selection is required.";
            }
        }

        return $errors;
    }

    /**
     * @return array<int, array{driver_id: int|null, trip_date: string, items: array<int, PartnerRequestItem>}>
     */
    public function groupItemsByDriverAndDate(Collection $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $driverId = $item->driver_id ?: null;
            $tripDate = $item->trip_date instanceof \Carbon\CarbonInterface
                ? $item->trip_date->toDateString()
                : (string) $item->trip_date;
            $key = ($driverId ?: 'unassigned') . '|' . $tripDate;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'driver_id' => $driverId,
                    'trip_date' => $tripDate,
                    'items' => [],
                ];
            }

            $grouped[$key]['items'][] = $item;
        }

        return array_values($grouped);
    }

    /**
     * @return array<string, mixed>
     */
    public function mapItemToCrewData(PartnerRequestItem $item): array
    {
        return [
            'vessel_id' => $item->vessel_id,
            'name' => $item->name,
            'phone' => $item->phone,
            'phone_2' => $item->phone_2,
            'address' => $item->address,
            'pick_up_time' => $item->pick_up_time,
            'from_location' => $item->from_location,
            'to_location' => $item->to_location,
            'flight_number' => $item->flight_number,
            'remarks' => $item->remarks,
            'sub_remark' => $item->sub_remark,
        ];
    }
}
