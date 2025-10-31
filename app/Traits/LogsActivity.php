<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    /**
     * Boot the trait and register model event listeners.
     */
    protected static function bootLogsActivity(): void
    {
        // Log when model is created
        static::created(function ($model) {
            $model->logActivity('created', null, $model->getAttributes());
        });

        // Log when model is updated
        static::updated(function ($model) {
            $model->logActivity('updated', $model->getOriginal(), $model->getChanges());
        });

        // Log when model is deleted
        static::deleted(function ($model) {
            $model->logActivity('deleted', $model->getAttributes(), null);
        });
    }

    /**
     * Log an activity for this model.
     *
     * @param string $action
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return ActivityLog
     */
    public function logActivity(string $action, ?array $oldValues = null, ?array $newValues = null): ActivityLog
    {
        // Remove timestamps from old/new values to reduce noise
        if ($oldValues) {
            unset($oldValues['created_at'], $oldValues['updated_at']);
        }
        if ($newValues) {
            unset($newValues['created_at'], $newValues['updated_at']);
        }

        // Generate description
        $description = $this->getActivityDescription($action, $oldValues, $newValues);

        return ActivityLog::create([
            'loggable_type' => get_class($this),
            'loggable_id' => $this->id ?? 0,
            'action' => $action,
            'user_id' => Auth::id(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get a human-readable description of the activity.
     *
     * @param string $action
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return string
     */
    protected function getActivityDescription(string $action, ?array $oldValues, ?array $newValues): string
    {
        $modelName = class_basename($this);
        
        return match($action) {
            'created' => "{$modelName} was created",
            'updated' => "{$modelName} was updated",
            'deleted' => "{$modelName} was deleted",
            default => "{$modelName} action: {$action}",
        };
    }

    /**
     * Get all activity logs for this model.
     */
    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable')->latest();
    }

    /**
     * Get the latest activity log for this model.
     */
    public function latestActivityLog()
    {
        return $this->morphOne(ActivityLog::class, 'loggable')->latestOfMany();
    }
}

