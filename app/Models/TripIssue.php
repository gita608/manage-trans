<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class TripIssue extends Model
{
    use LogsActivity;
    protected $fillable = [
        'trip_id',
        'driver_id',
        'issue_type_id',
        'description',
    ];

    /**
     * Get the trip that owns the issue.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the driver who reported the issue.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the issue type.
     */
    public function issueType(): BelongsTo
    {
        return $this->belongsTo(TripIssueType::class, 'issue_type_id');
    }
}
