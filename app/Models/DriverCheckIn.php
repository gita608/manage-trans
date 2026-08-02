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
     * Configured auto check-out duration in hours (from settings).
     */
    public static function autoCheckoutHours(): int
    {
        $hours = (int) getSetting('check_in_auto_checkout_hours', self::DEFAULT_AUTO_CHECKOUT_HOURS);

        return max(1, $hours);
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

    public function autoCheckoutDueAt(): Carbon
    {
        return $this->check_in_at->copy()->addHours(self::autoCheckoutHours());
    }

    public function isExpired(?Carbon $now = null): bool
    {
        $now = $now ?? Carbon::now(getAppTimezone());

        return $this->isActive() && $now->greaterThanOrEqualTo($this->autoCheckoutDueAt());
    }

    /**
     * Close this check-in immediately (vehicle switch).
     */
    public function closeNow(?Carbon $at = null): void
    {
        $at = $at ?? Carbon::now(getAppTimezone());

        $this->forceFill([
            'checked_out_at' => $at,
            'status' => self::STATUS_CHECKED_OUT,
        ])->save();
    }

    /**
     * Close this check-in via configured auto expiry.
     */
    public function closeForAutoExpiry(): void
    {
        $this->forceFill([
            'checked_out_at' => $this->autoCheckoutDueAt(),
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
     * Auto-close expired active check-ins. Returns how many were closed.
     */
    public static function autoCheckoutExpired(?Carbon $now = null): int
    {
        $now = $now ?? Carbon::now(getAppTimezone());
        $cutoff = $now->copy()->subHours(self::autoCheckoutHours());

        $expired = static::query()
            ->active()
            ->where('check_in_at', '<=', $cutoff)
            ->get();

        foreach ($expired as $checkIn) {
            $checkIn->closeForAutoExpiry();
        }

        return $expired->count();
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
