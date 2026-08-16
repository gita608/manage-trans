<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverCheckIn extends Model
{
    use HasFactory, LogsActivity;

    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_CHECKED_OUT = 'checked_out';

    public const DEFAULT_AUTO_CHECKOUT_HOURS = 12;

    /**
     * Configured daily auto check-out duration in hours (from settings).
     */
    public static function autoCheckoutHours(): int
    {
        $hours = (int) getSetting('check_in_auto_checkout_hours', self::DEFAULT_AUTO_CHECKOUT_HOURS);

        return max(1, $hours);
    }

    /**
     * Configured cumulative daily allowance in seconds.
     */
    public static function dailyLimitSeconds(): int
    {
        return self::autoCheckoutHours() * 3600;
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'check_in_date',
        'check_in_time',
        'check_in_at',
        'start_km',
        'checked_out_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'start_km' => 'decimal:2',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_CHECKED_IN;
    }

    /**
     * Normalize a duty-day date string in the app timezone.
     */
    public static function normalizeDutyDate(string|Carbon $date): string
    {
        if ($date instanceof Carbon) {
            return $date->copy()->timezone(getAppTimezone())->toDateString();
        }

        return Carbon::parse($date, getAppTimezone())->toDateString();
    }

    /**
     * Seconds already consumed by a driver on a duty day.
     *
     * @param  int|null  $excludeId  Exclude a session (usually the current one) from the total
     * @param  Carbon|null  $asOf  Cap any active session contribution at this timestamp
     */
    public static function usedSecondsForDriverDay(
        int $driverId,
        string|Carbon $date,
        ?int $excludeId = null,
        ?Carbon $asOf = null
    ): int {
        $asOf = $asOf ?? Carbon::now(getAppTimezone());
        $dutyDate = self::normalizeDutyDate($date);

        $sessions = static::query()
            ->where('driver_id', $driverId)
            ->whereDate('check_in_date', $dutyDate)
            ->when($excludeId, fn (Builder $q) => $q->where('id', '!=', $excludeId))
            ->orderBy('check_in_at')
            ->orderBy('id')
            ->get();

        $used = 0;

        foreach ($sessions as $session) {
            $used += $session->durationSeconds($asOf);
        }

        return max(0, $used);
    }

    /**
     * Remaining cumulative daily allowance for a driver duty day.
     */
    public static function remainingSecondsForDriverDay(
        int $driverId,
        string|Carbon $date,
        ?int $excludeId = null,
        ?Carbon $asOf = null
    ): int {
        return max(
            0,
            self::dailyLimitSeconds() - self::usedSecondsForDriverDay($driverId, $date, $excludeId, $asOf)
        );
    }

    public static function dailyLimitReached(
        int $driverId,
        string|Carbon $date,
        ?int $excludeId = null,
        ?Carbon $asOf = null
    ): bool {
        return self::remainingSecondsForDriverDay($driverId, $date, $excludeId, $asOf) <= 0;
    }

    /**
     * Duration contributed by this session in whole seconds.
     */
    public function durationSeconds(?Carbon $asOf = null): int
    {
        if (!$this->check_in_at) {
            return 0;
        }

        $start = $this->check_in_at->copy();
        $asOf = $asOf ?? Carbon::now(getAppTimezone());

        if ($this->status === self::STATUS_CHECKED_OUT) {
            $end = $this->checked_out_at?->copy() ?? $start;
        } else {
            $end = $asOf->copy();
        }

        if ($end->lessThan($start)) {
            return 0;
        }

        return max(0, $end->getTimestamp() - $start->getTimestamp());
    }

    /**
     * When this active session must auto check-out based on remaining daily allowance
     * at the moment this session started (not a fresh full-day allotment).
     */
    public function autoCheckoutDueAt(): Carbon
    {
        $usedBeforeThisSession = self::usedSecondsForDriverDay(
            (int) $this->driver_id,
            $this->check_in_date,
            excludeId: $this->id ? (int) $this->id : null,
            asOf: $this->check_in_at
        );

        $remainingAtStart = max(0, self::dailyLimitSeconds() - $usedBeforeThisSession);

        return $this->check_in_at->copy()->addSeconds($remainingAtStart);
    }

    public function isExpired(?Carbon $now = null): bool
    {
        $now = $now ?? Carbon::now(getAppTimezone());

        return $this->isActive() && $now->greaterThanOrEqualTo($this->autoCheckoutDueAt());
    }

    /**
     * Close this check-in immediately (vehicle switch), never past its allowed expiry.
     */
    public function closeNow(?Carbon $at = null): void
    {
        $at = $at ?? Carbon::now(getAppTimezone());
        $dueAt = $this->autoCheckoutDueAt();

        if ($at->greaterThan($dueAt)) {
            $at = $dueAt;
        }

        if ($at->lessThan($this->check_in_at)) {
            $at = $this->check_in_at->copy();
        }

        $this->forceFill([
            'checked_out_at' => $at,
            'status' => self::STATUS_CHECKED_OUT,
        ])->save();
    }

    /**
     * Close this check-in at its cumulative daily allowance expiry.
     */
    public function closeForAutoExpiry(): void
    {
        $dueAt = $this->autoCheckoutDueAt();

        if ($dueAt->lessThan($this->check_in_at)) {
            $dueAt = $this->check_in_at->copy();
        }

        $this->forceFill([
            'checked_out_at' => $dueAt,
            'status' => self::STATUS_CHECKED_OUT,
        ])->save();
    }

    /**
     * @param  Builder<DriverCheckIn>  $query
     * @return Builder<DriverCheckIn>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CHECKED_IN);
    }

    /**
     * Auto-close active check-ins whose cumulative daily allowance has elapsed.
     */
    public static function autoCheckoutExpired(?Carbon $now = null): int
    {
        $now = $now ?? Carbon::now(getAppTimezone());
        $closed = 0;

        static::query()
            ->active()
            ->orderBy('check_in_at')
            ->orderBy('id')
            ->chunkById(100, function ($checkIns) use ($now, &$closed) {
                foreach ($checkIns as $checkIn) {
                    if ($checkIn->isExpired($now)) {
                        $checkIn->closeForAutoExpiry();
                        $closed++;
                    }
                }
            });

        return $closed;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getActivityConfig(): array
    {
        return [
            'model_name' => 'Driver Check-In',
            'identifier_field' => 'id',
            'field_mappings' => [
                'vehicle_id' => 'vehicle',
                'check_in_date' => 'check-in date',
                'check_in_time' => 'check-in time',
                'start_km' => 'start km',
                'status' => 'status',
            ],
        ];
    }
}
