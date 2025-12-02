<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use LogsActivity;
    protected $fillable = [
        'user_id',
        'driver_id',
        'title',
        'message',
        'type',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Get activity configuration for this model.
     *
     * @return array
     */
    protected function getActivityConfig(): array
    {
        return [
            'model_name' => 'Notification',
            'identifier_field' => 'title',
            'identifier_label_callback' => function($model) {
                if ($model->driver_id) {
                    return $model->driver ? "to {$model->driver->name}" : "to driver #{$model->driver_id}";
                }
                return 'to all drivers';
            },
            'field_mappings' => [
                'title' => 'title',
                'message' => 'message',
                'driver_id' => function($oldValue, $newValue, $model) {
                    if ($newValue === null) {
                        return "recipient changed to all drivers";
                    }
                    $driver = Driver::find($newValue);
                    $driverName = $driver ? $driver->name : "driver #{$newValue}";
                    if ($oldValue === null) {
                        return "recipient changed from all drivers to {$driverName}";
                    }
                    $oldDriver = Driver::find($oldValue);
                    $oldDriverName = $oldDriver ? $oldDriver->name : "driver #{$oldValue}";
                    return "recipient changed from {$oldDriverName} to {$driverName}";
                },
                'is_read' => function($oldValue, $newValue) {
                    if ($newValue) {
                        return "read status changed to read";
                    }
                    return "read status changed to unread";
                },
            ],
        ];
    }
}
