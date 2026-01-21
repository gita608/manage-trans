<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get activity configuration for this model.
     *
     * @return array
     */
    protected function getActivityConfig(): array
    {
        return [
            'model_name' => 'Partner',
            'identifier_field' => 'title',
            'field_mappings' => [
                'title' => 'title',
                'is_default' => 'is_default',
            ],
        ];
    }
}
