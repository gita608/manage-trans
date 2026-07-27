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
        'input_types',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'input_types' => 'array',
    ];

    /**
     * Get configured input types with default fallback.
     *
     * @return array
     */
    public function getInputTypesAttribute($value): array
    {
        if (empty($value)) {
            return ['number', 'image'];
        }
        
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        return is_array($decoded) && !empty($decoded) ? $decoded : ['number', 'image'];
    }

    /**
     * Check if a specific input type is enabled.
     *
     * @param string $type
     * @return bool
     */
    public function hasInputType(string $type): bool
    {
        return in_array($type, $this->input_types ?? ['number', 'image']);
    }

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
