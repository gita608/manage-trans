<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripExpenseType extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title',
    ];

    /**
     * Get the trip expenses for this expense type.
     */
    public function tripExpenses(): HasMany
    {
        return $this->hasMany(TripExpense::class, 'expense_type_id');
    }

    /**
     * Get activity configuration for this model.
     *
     * @return array
     */
    protected function getActivityConfig(): array
    {
        return [
            'model_name' => 'Trip Expense Type',
            'identifier_field' => 'title',
            'field_mappings' => [
                'title' => 'title',
            ],
        ];
    }
}
