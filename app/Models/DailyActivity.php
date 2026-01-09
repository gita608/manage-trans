<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyActivity extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'driver_id',
        'image',
        'note',
        'activity_date',
        'kilometers_driven',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'kilometers_driven' => 'decimal:2',
        ];
    }

    /**
     * Get the driver that owns the daily activity.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
