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

        // Determine user_id and driver_id based on authenticated model
        $user = Auth::user();
        $userId = null;
        $driverId = null;

        if ($user) {
            if ($user instanceof \App\Models\User) {
                $userId = $user->id;
            } elseif ($user instanceof \App\Models\Driver) {
                $driverId = $user->id;
            }
        }

        return ActivityLog::create([
            'loggable_type' => get_class($this),
            'loggable_id' => $this->id ?? 0,
            'action' => $action,
            'user_id' => $userId,
            'driver_id' => $driverId,
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
        // Check if model has custom description method (for complex cases like Trip)
        if (method_exists($this, 'getCustomActivityDescription')) {
            return $this->getCustomActivityDescription($action, $oldValues, $newValues);
        }

        $modelName = $this->getActivityModelName();
        $identifier = $this->getActivityIdentifier();
        $identifierLabel = $this->getActivityIdentifierLabel();
        
        return match($action) {
            'created' => $this->formatCreatedDescription($modelName, $identifier, $identifierLabel),
            'updated' => $this->formatUpdatedDescription($modelName, $identifier, $oldValues, $newValues),
            'deleted' => $this->formatDeletedDescription($modelName, $identifier),
            default => "{$modelName} action: {$action}",
        };
    }

    /**
     * Get activity configuration for this model.
     * Override in models to customize behavior.
     *
     * @return array
     */
    protected function getActivityConfig(): array
    {
        return [];
    }

    /**
     * Get the model name for activity descriptions.
     */
    protected function getActivityModelName(): string
    {
        return $this->getActivityConfig()['model_name'] ?? class_basename($this);
    }

    /**
     * Get the identifier value for this model (e.g., name, title, etc.).
     */
    protected function getActivityIdentifier(): string
    {
        $config = $this->getActivityConfig();
        $identifierField = $config['identifier_field'] ?? 'name';
        return $this->{$identifierField} ?? 'Unknown';
    }

    /**
     * Get the identifier label (e.g., type, role, status).
     */
    protected function getActivityIdentifierLabel(): ?string
    {
        $config = $this->getActivityConfig();
        if (!isset($config['identifier_label_callback'])) {
            return null;
        }
        
        $callback = $config['identifier_label_callback'];
        return is_callable($callback) ? $callback($this) : null;
    }

    /**
     * Format the description for created action.
     */
    protected function formatCreatedDescription(string $modelName, string $identifier, ?string $label): string
    {
        $labelPart = $label ? " ({$label})" : '';
        return "{$modelName} '{$identifier}'{$labelPart} was created";
    }

    /**
     * Format the description for updated action.
     */
    protected function formatUpdatedDescription(string $modelName, string $identifier, ?array $oldValues, ?array $newValues): string
    {
        if (empty($newValues)) {
            return "{$modelName} '{$identifier}' was updated";
        }

        $changes = $this->buildChangeDescriptions($oldValues, $newValues);
        
        if (empty($changes)) {
            return "{$modelName} '{$identifier}' was updated";
        }
        
        return "{$modelName} '{$identifier}' updated: " . implode(', ', $changes);
    }

    /**
     * Format the description for deleted action.
     */
    protected function formatDeletedDescription(string $modelName, string $identifier): string
    {
        return "{$modelName} '{$identifier}' was deleted";
    }

    /**
     * Build change descriptions from old and new values.
     */
    protected function buildChangeDescriptions(?array $oldValues, ?array $newValues): array
    {
        $config = $this->getActivityConfig();
        $fieldMappings = $config['field_mappings'] ?? [];
        $changes = [];

        foreach ($newValues as $field => $newValue) {
            // Skip password fields - handle specially
            if ($field === 'password') {
                $changes[] = "password was changed";
                continue;
            }

            $oldValue = $oldValues[$field] ?? null;
            
            // Use field mapping if available
            if (isset($fieldMappings[$field])) {
                $mapping = $fieldMappings[$field];
                
                if (is_callable($mapping)) {
                    // Custom callback for complex transformations
                    $change = $mapping($oldValue, $newValue, $this);
                    if ($change) {
                        $changes[] = $change;
                    }
                } elseif (is_array($mapping)) {
                    // Array mapping (e.g., enum values)
                    if (isset($mapping['label'])) {
                        $fieldLabel = $mapping['label'];
                        $oldLabel = $mapping[$oldValue] ?? $oldValue ?? 'unknown';
                        $newLabel = $mapping[$newValue] ?? $newValue ?? 'unknown';
                        $changes[] = "{$fieldLabel} changed from '{$oldLabel}' to '{$newLabel}'";
                    } else {
                        // Simple array mapping
                        $oldLabel = $mapping[$oldValue] ?? $oldValue ?? 'unknown';
                        $newLabel = $mapping[$newValue] ?? $newValue ?? 'unknown';
                        $fieldLabel = str_replace('_', ' ', ucwords($field, '_'));
                        $changes[] = "{$fieldLabel} changed from '{$oldLabel}' to '{$newLabel}'";
                    }
                } else {
                    // Simple label string
                    $fieldLabel = $mapping;
                    if ($oldValue !== null) {
                        $changes[] = "{$fieldLabel} changed from '{$oldValue}' to '{$newValue}'";
                    } else {
                        $changes[] = "{$fieldLabel} updated";
                    }
                }
            } else {
                // Default: use field name with formatting
                $fieldLabel = str_replace('_', ' ', ucwords($field, '_'));
                if ($oldValue !== null) {
                    $changes[] = "{$fieldLabel} changed from '{$oldValue}' to '{$newValue}'";
                } else {
                    $changes[] = "{$fieldLabel} updated";
                }
            }
        }

        return $changes;
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

