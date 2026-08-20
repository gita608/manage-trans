<?php

namespace App\Support;

use App\Models\PartnerRequest;
use App\Models\PartnerRequestItem;
use Carbon\CarbonInterface;

class PartnerRequestReviewVersion
{
    public static function make(PartnerRequest $partnerRequest): string
    {
        if (!$partnerRequest->relationLoaded('items')) {
            $partnerRequest->load('items');
        }

        $payload = [
            'request' => self::requestPayload($partnerRequest),
            'items' => $partnerRequest->items
                ->sortBy('id')
                ->values()
                ->map(fn (PartnerRequestItem $item) => self::itemPayload($item))
                ->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public static function matches(PartnerRequest $partnerRequest, string $submittedVersion): bool
    {
        return hash_equals(self::make($partnerRequest), $submittedVersion);
    }

    public static function isStale(PartnerRequest $partnerRequest, string $submittedVersion): bool
    {
        return !self::matches($partnerRequest, $submittedVersion);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function requestPayload(PartnerRequest $partnerRequest): array
    {
        return [
            'id' => $partnerRequest->id,
            'status' => $partnerRequest->status,
            'partner_updated_at' => self::normalizeDateTime($partnerRequest->partner_updated_at),
            'updated_at' => self::normalizeDateTime($partnerRequest->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function itemPayload(PartnerRequestItem $item): array
    {
        return [
            'id' => $item->id,
            'trip_date' => self::normalizeDate($item->trip_date),
            'pick_up_time' => self::normalizeTime($item->pick_up_time),
            'name' => $item->name,
            'phone' => $item->phone,
            'phone_2' => $item->phone_2,
            'address' => $item->address,
            'from_location' => $item->from_location,
            'to_location' => $item->to_location,
            'flight_number' => $item->flight_number,
            'remarks' => $item->remarks,
            'sub_remark' => $item->sub_remark,
            'vessel_name_raw' => $item->vessel_name_raw,
            'vessel_id' => $item->vessel_id,
            'driver_id' => $item->driver_id,
        ];
    }

    protected static function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return (string) $value;
    }

    protected static function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('H:i:s');
        }

        $stringValue = (string) $value;

        if (preg_match('/^\d{2}:\d{2}$/', $stringValue) === 1) {
            return $stringValue . ':00';
        }

        return $stringValue;
    }

    protected static function normalizeDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        return (string) $value;
    }
}
