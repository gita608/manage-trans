<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class TripExpense extends Model
{
    use LogsActivity;
    protected $fillable = [
        'trip_id',
        'driver_id',
        'expense_type_id',
        'amount',
        'hours',
        'description',
        'receipt',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'hours' => 'decimal:2',
        ];
    }

    /**
     * Get the trip that owns the expense.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the driver who submitted the expense.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the expense type.
     */
    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(TripExpenseType::class, 'expense_type_id');
    }

    /**
     * Format amount for display. Hours-only types show "-" instead of 0.00.
     */
    public function displayAmount(): string
    {
        if ($this->expenseType && $this->expenseType->hasInputType('amount')) {
            return number_format((float) $this->amount, 2);
        }

        return '-';
    }

    /**
     * Format hours for display with "hrs" suffix.
     * Non-null legacy hours remain visible even if the type no longer includes hours.
     */
    public function displayHours(): string
    {
        $usesHours = $this->expenseType && $this->expenseType->hasInputType('hours');

        if (!$usesHours && $this->hours === null) {
            return '-';
        }

        if ($this->hours === null) {
            return '-';
        }

        return number_format((float) $this->hours, 2) . ' hrs';
    }
}
