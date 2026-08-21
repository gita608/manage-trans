<?php

namespace App\Services;

use App\Models\Vessel;
use Carbon\Carbon;

class PartnerScheduleParser
{
    /**
     * @var array<string, Vessel|null>|null
     */
    private ?array $normalizedVesselLookup = null;

    /**
     * Convert Textract table rows into PartnerRequestItem-compatible attributes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseTableRows(array $tableRows, ?string $defaultDate = null): array
    {
        $parsedItems = [];
        $tripDate = null;

        if ($defaultDate) {
            $tripDate = Carbon::parse($defaultDate);
        }

        if (empty($tableRows)) {
            return [];
        }

        $dataStartIndex = 1;

        if (!empty($tableRows[0])) {
            $firstRowText = implode(' ', array_map('trim', $tableRows[0]));
            if (preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/', $firstRowText, $matches)) {
                try {
                    $tripDate = Carbon::createFromFormat('d F Y', $matches[1] . ' ' . $matches[2] . ' ' . $matches[3]);
                } catch (\Exception $e) {
                    // Keep existing trip date (may still be null).
                }
            }
        }

        for ($i = $dataStartIndex; $i < count($tableRows); $i++) {
            $row = $tableRows[$i];

            if (count($row) < 3) {
                continue;
            }

            $crewName = trim($row[0] ?? '');
            $vesselName = trim($row[2] ?? '');
            $pickUpTime = trim($row[3] ?? '');
            $fromLocation = trim($row[4] ?? '');
            $toLocation = trim($row[5] ?? '');
            $remarks = trim($row[6] ?? '');
            $crewPhone = trim($row[7] ?? '');
            $crewPhone2 = trim($row[8] ?? '');

            if ($this->isHeaderRow($row)) {
                continue;
            }

            if ($crewName === '' && $vesselName === '') {
                continue;
            }

            if ($crewPhone === '' && $crewName !== '') {
                if (preg_match('/(?:Mobile\s*(?:no\.?|number)?[:\-]?\s*)(\d+)/i', $crewName, $matches)) {
                    $crewPhone = $matches[1];
                    $crewName = trim(preg_replace('/\s*-\s*Mobile\s*(?:no\.?|number)?[:\-]?\s*\d+/i', '', $crewName));
                }
            }

            $vesselMatch = $this->matchVessel($vesselName);

            $parsedItems[] = [
                'trip_date' => $tripDate?->format('Y-m-d'),
                'pick_up_time' => $this->parsePickUpTime($pickUpTime),
                'name' => $crewName !== '' ? $crewName : null,
                'phone' => $crewPhone !== '' ? $crewPhone : null,
                'phone_2' => $crewPhone2 !== '' ? $crewPhone2 : null,
                'address' => null,
                'from_location' => $fromLocation !== '' ? $fromLocation : null,
                'to_location' => $toLocation !== '' ? $toLocation : null,
                'flight_number' => null,
                'remarks' => $remarks !== '' ? $remarks : null,
                'sub_remark' => null,
                'vessel_name_raw' => $vesselName !== '' ? $vesselName : null,
                'vessel_id' => $vesselMatch?->id,
                'driver_id' => null,
            ];
        }

        return $parsedItems;
    }

    protected function matchVessel(string $vesselName): ?Vessel
    {
        $normalizedName = $this->normalizeVesselName($vesselName);

        if ($normalizedName === '') {
            return null;
        }

        $vessel = $this->normalizedVesselLookup()[$normalizedName] ?? null;

        return $vessel instanceof Vessel ? $vessel : null;
    }

    protected function normalizeVesselName(string $name): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($name));

        return mb_strtolower($collapsed ?? '');
    }

    /**
     * @return array<string, Vessel|null>
     */
    protected function normalizedVesselLookup(): array
    {
        if ($this->normalizedVesselLookup !== null) {
            return $this->normalizedVesselLookup;
        }

        $lookup = [];

        foreach (Vessel::query()->get(['id', 'name']) as $vessel) {
            $normalizedName = $this->normalizeVesselName($vessel->name);

            if ($normalizedName === '') {
                continue;
            }

            if (array_key_exists($normalizedName, $lookup)) {
                $lookup[$normalizedName] = null;

                continue;
            }

            $lookup[$normalizedName] = $vessel;
        }

        $this->normalizedVesselLookup = $lookup;

        return $lookup;
    }

    protected function isHeaderRow(array $row): bool
    {
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
            'confirm actioin',
            'action',
        ];

        $rowText = implode(' ', array_map('strtolower', array_map('trim', $row)));
        $matches = 0;

        foreach ($headerPatterns as $pattern) {
            if (stripos($rowText, $pattern) !== false) {
                $matches++;
            }
        }

        if ($matches >= 3) {
            return true;
        }

        if (count($row) >= 3) {
            $firstCol = strtolower(trim($row[0] ?? ''));
            $secondCol = strtolower(trim($row[1] ?? ''));
            $thirdCol = strtolower(trim($row[2] ?? ''));

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

    protected function parsePickUpTime(?string $timeString): ?string
    {
        $timeString = trim((string) $timeString);

        if ($timeString === '') {
            return null;
        }

        if (preg_match('/(\d{1,2})(\d{2})(AM|PM)/i', $timeString, $matches)) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];
            $meridian = strtoupper($matches[3]);

            if ($meridian === 'PM' && $hour < 12) {
                $hour += 12;
            } elseif ($meridian === 'AM' && $hour === 12) {
                $hour = 0;
            }

            return sprintf('%02d:%02d', $hour, $minute);
        }

        try {
            return Carbon::createFromFormat('g:i A', $timeString)->format('H:i');
        } catch (\Exception $e) {
            try {
                return Carbon::createFromFormat('H:i', $timeString)->format('H:i');
            } catch (\Exception $e2) {
                return null;
            }
        }
    }
}
