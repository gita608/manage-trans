<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripIssueType extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title',
    ];

    /**
     * Get the trip issues for this issue type.
     */
    public function tripIssues(): HasMany
    {
        return $this->hasMany(TripIssue::class, 'issue_type_id');
    }

    /**
     * Get activity configuration for this model.
     *
     * @return array
     */
    protected function getActivityConfig(): array
    {
        return [
            'model_name' => 'Trip Issue Type',
            'identifier_field' => 'title',
            'field_mappings' => [
                'title' => 'title',
            ],
        ];
    }
}
