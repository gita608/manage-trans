<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripExpense extends Model
{
    protected $fillable = [
        'trip_id',
        'driver_id',
        'expense_type_id',
        'amount',
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
}
