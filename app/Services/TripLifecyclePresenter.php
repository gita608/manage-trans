<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Driver;
use App\Models\Partner;
use App\Models\Trip;
use App\Models\TripCrew;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TripLifecyclePresenter
{
    /**
     * Fields that must never appear in Trip Details change lists.
     */
    protected array $hiddenFields = [
        'id',
        'trip_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'user_id',
        'loggable_id',
        'loggable_type',
        'remember_token',
        'password',
        'notification_token',
    ];

    /**
     * Build lifecycle summary, stepper steps, and friendly timeline for Trip Details.
     */
    public function present(Trip $trip): array
    {
        $logs = $trip->activityLogs
            ->sortBy('created_at')
            ->values();

        $startedLog = $this->firstStatusTransition($logs, TripCrew::STATUS_IN_PROGRESS);
        $completedLog = $this->firstStatusTransition($logs, TripCrew::STATUS_COMPLETED);
        $cancelledLog = $this->firstStatusTransition($logs, TripCrew::STATUS_CANCELLED);
        $assignment = $this->resolveAssignment($trip, $logs);

        $startedAt = $startedLog?->created_at ? Carbon::parse($startedLog->created_at) : null;
        $completedAt = $completedLog?->created_at ? Carbon::parse($completedLog->created_at) : null;
        $cancelledAt = $cancelledLog?->created_at ? Carbon::parse($cancelledLog->created_at) : null;
        $createdAt = Carbon::parse($trip->created_at);

        $isCancelled = $trip->status === TripCrew::STATUS_CANCELLED;
        $isCompleted = $trip->status === TripCrew::STATUS_COMPLETED;
        $isInProgress = $trip->status === TripCrew::STATUS_IN_PROGRESS;
        $isAssigned = (bool) $trip->driver_id || $trip->status === TripCrew::STATUS_ASSIGNED;

        $duration = null;
        $durationLabel = null;
        $runningFor = null;

        if ($startedAt && $completedAt && $isCompleted) {
            $duration = $this->formatDuration($startedAt, $completedAt);
            $durationLabel = 'Actual Trip Duration';
        } elseif ($startedAt && $isInProgress) {
            $runningFor = $this->formatDuration($startedAt, now());
        } elseif ($isCompleted && !$startedAt) {
            $durationLabel = 'Actual start time not recorded';
        }

        $timeline = $this->buildTimeline($trip, $logs, $startedAt, $completedAt, $duration);
        $steps = $this->buildSteps(
            $trip,
            $createdAt,
            $assignment,
            $startedAt,
            $completedAt,
            $cancelledAt,
            $isCancelled,
            $isCompleted,
            $isInProgress,
            $runningFor
        );

        $createdBy = $this->actorFromLog($logs->firstWhere('action', 'created'));

        return [
            'summary' => [
                'status' => $this->statusLabel($trip->status),
                'status_badge' => $trip->getStatusBadge(),
                'scheduled_date' => $trip->trip_date
                    ? Carbon::parse($trip->trip_date)->format('M j, Y')
                    : null,
                'driver' => $trip->driver?->name,
                'created_at' => $createdAt,
                'created_by' => $createdBy,
                'assigned_at' => $assignment['time'] ?? null,
                'assigned_driver' => $assignment['driver_name'] ?? $trip->driver?->name,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'cancelled_at' => $cancelledAt,
                'duration' => $duration,
                'duration_label' => $durationLabel,
                'running_for' => $runningFor,
                'is_cancelled' => $isCancelled,
                'is_completed' => $isCompleted,
                'is_in_progress' => $isInProgress,
                'awaiting_driver' => !$trip->driver_id && !$isCancelled,
            ],
            'steps' => $steps,
            'timeline' => $timeline,
            'timeline_count' => count($timeline),
        ];
    }

    protected function buildSteps(
        Trip $trip,
        Carbon $createdAt,
        array $assignment,
        ?Carbon $startedAt,
        ?Carbon $completedAt,
        ?Carbon $cancelledAt,
        bool $isCancelled,
        bool $isCompleted,
        bool $isInProgress,
        ?string $runningFor
    ): array {
        $hasDriver = (bool) $trip->driver_id;
        $steps = [];

        $steps[] = [
            'key' => 'created',
            'title' => 'Schedule Created',
            'state' => 'completed',
            'time' => $createdAt,
            'meta' => null,
            'icon' => 'ri-calendar-check-line',
        ];

        if ($isCancelled && !$startedAt) {
            if ($hasDriver || ($assignment['time'] ?? null)) {
                $steps[] = [
                    'key' => 'assigned',
                    'title' => 'Driver Assigned',
                    'state' => 'completed',
                    'time' => $assignment['time'] ?? $createdAt,
                    'meta' => $assignment['driver_name'] ?? $trip->driver?->name,
                    'icon' => 'ri-user-follow-line',
                ];
            }

            $steps[] = [
                'key' => 'cancelled',
                'title' => 'Trip Cancelled',
                'state' => 'cancelled',
                'time' => $cancelledAt,
                'meta' => null,
                'icon' => 'ri-close-circle-line',
            ];

            return $steps;
        }

        if ($hasDriver) {
            $steps[] = [
                'key' => 'assigned',
                'title' => 'Driver Assigned',
                'state' => 'completed',
                'time' => $assignment['time'] ?? $createdAt,
                'meta' => $assignment['driver_name'] ?? $trip->driver?->name,
                'icon' => 'ri-user-follow-line',
            ];
        } else {
            $steps[] = [
                'key' => 'assigned',
                'title' => 'Awaiting Driver',
                'state' => $isCancelled ? 'cancelled' : 'current',
                'time' => null,
                'meta' => null,
                'icon' => 'ri-user-unfollow-line',
            ];
        }

        if ($isCancelled && $startedAt) {
            $steps[] = [
                'key' => 'started',
                'title' => 'Trip Started',
                'state' => 'completed',
                'time' => $startedAt,
                'meta' => null,
                'icon' => 'ri-play-circle-line',
            ];
            $steps[] = [
                'key' => 'cancelled',
                'title' => 'Trip Cancelled',
                'state' => 'cancelled',
                'time' => $cancelledAt,
                'meta' => null,
                'icon' => 'ri-close-circle-line',
            ];

            return $steps;
        }

        if ($startedAt) {
            $steps[] = [
                'key' => 'started',
                'title' => 'Trip Started',
                'state' => 'completed',
                'time' => $startedAt,
                'meta' => null,
                'icon' => 'ri-play-circle-line',
            ];
        } else {
            $steps[] = [
                'key' => 'started',
                'title' => 'Trip Started',
                'state' => (!$hasDriver || $isCancelled) ? 'pending' : ($isInProgress ? 'current' : 'pending'),
                'time' => null,
                'meta' => null,
                'icon' => 'ri-play-circle-line',
            ];
        }

        if ($isCompleted && $completedAt) {
            $steps[] = [
                'key' => 'completed',
                'title' => 'Trip Completed',
                'state' => 'completed',
                'time' => $completedAt,
                'meta' => null,
                'icon' => 'ri-checkbox-circle-line',
            ];
        } elseif ($isInProgress && $startedAt) {
            $steps[] = [
                'key' => 'completed',
                'title' => 'Trip In Progress',
                'state' => 'current',
                'time' => null,
                'meta' => $runningFor ? ('Running for ' . $runningFor) : 'In progress',
                'icon' => 'ri-loader-4-line',
            ];
        } else {
            $steps[] = [
                'key' => 'completed',
                'title' => 'Trip Completed',
                'state' => 'pending',
                'time' => null,
                'meta' => null,
                'icon' => 'ri-checkbox-circle-line',
            ];
        }

        return $steps;
    }

    protected function buildTimeline(
        Trip $trip,
        Collection $logs,
        ?Carbon $startedAt,
        ?Carbon $completedAt,
        ?string $duration
    ): array {
        $events = [];

        foreach ($logs as $log) {
            $event = $this->presentLog($trip, $log);
            if ($event) {
                $events[] = $event;
            }
        }

        // Attach duration note on the completed event when available
        if ($duration && $startedAt && $completedAt) {
            foreach ($events as &$event) {
                if (($event['event_key'] ?? null) === 'completed') {
                    $event['duration'] = $duration;
                    break;
                }
            }
            unset($event);
        }

        return $events;
    }

    protected function presentLog(Trip $trip, ActivityLog $log): ?array
    {
        $actor = $this->actorFromLog($log);
        $time = Carbon::parse($log->created_at);
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];
        $changes = $this->friendlyChanges($old, $new);

        if ($log->action === 'created') {
            $driverId = $new['driver_id'] ?? $trip->driver_id;
            $driverName = $driverId ? $this->driverName($driverId) : null;

            return [
                'id' => $log->id,
                'event_key' => 'created',
                'title' => 'Schedule created',
                'description' => $driverName
                    ? ('Driver: ' . $driverName)
                    : 'Awaiting driver assignment',
                'actor_name' => $actor['name'],
                'actor_type' => $actor['type'],
                'icon' => 'ri-calendar-check-line',
                'color' => 'success',
                'badge' => 'Schedule Created',
                'time' => $time,
                'changes' => $changes,
                'duration' => null,
            ];
        }

        $oldStatus = $old['status'] ?? null;
        $newStatus = $new['status'] ?? null;
        $oldDriverId = array_key_exists('driver_id', $old) ? $old['driver_id'] : null;
        $newDriverId = array_key_exists('driver_id', $new) ? $new['driver_id'] : null;

        if ($newStatus === TripCrew::STATUS_IN_PROGRESS) {
            return [
                'id' => $log->id,
                'event_key' => 'started',
                'title' => 'Trip started',
                'description' => null,
                'actor_name' => $actor['name'],
                'actor_type' => $actor['type'],
                'icon' => 'ri-play-circle-line',
                'color' => 'info',
                'badge' => 'Trip Started',
                'time' => $time,
                'changes' => $this->statusOnlyChanges($changes),
                'duration' => null,
            ];
        }

        if ($newStatus === TripCrew::STATUS_COMPLETED) {
            return [
                'id' => $log->id,
                'event_key' => 'completed',
                'title' => 'Trip completed',
                'description' => null,
                'actor_name' => $actor['name'],
                'actor_type' => $actor['type'],
                'icon' => 'ri-checkbox-circle-line',
                'color' => 'success',
                'badge' => 'Trip Completed',
                'time' => $time,
                'changes' => $this->statusOnlyChanges($changes),
                'duration' => null,
            ];
        }

        if ($newStatus === TripCrew::STATUS_CANCELLED) {
            return [
                'id' => $log->id,
                'event_key' => 'cancelled',
                'title' => 'Trip cancelled',
                'description' => null,
                'actor_name' => $actor['name'],
                'actor_type' => $actor['type'],
                'icon' => 'ri-close-circle-line',
                'color' => 'danger',
                'badge' => 'Trip Cancelled',
                'time' => $time,
                'changes' => $this->statusOnlyChanges($changes),
                'duration' => null,
            ];
        }

        if (array_key_exists('driver_id', $new)) {
            $fromName = $oldDriverId ? $this->driverName($oldDriverId) : null;
            $toName = $newDriverId ? $this->driverName($newDriverId) : null;

            if (!$oldDriverId && $newDriverId) {
                return [
                    'id' => $log->id,
                    'event_key' => 'assigned',
                    'title' => 'Driver assigned',
                    'description' => $toName,
                    'actor_name' => $actor['name'],
                    'actor_type' => $actor['type'],
                    'icon' => 'ri-user-follow-line',
                    'color' => 'primary',
                    'badge' => 'Driver Assigned',
                    'time' => $time,
                    'changes' => $changes,
                    'duration' => null,
                ];
            }

            if ($oldDriverId && $newDriverId && (string) $oldDriverId !== (string) $newDriverId) {
                return [
                    'id' => $log->id,
                    'event_key' => 'reassigned',
                    'title' => 'Driver changed',
                    'description' => trim(($fromName ?? 'Unassigned') . ' → ' . ($toName ?? 'Unassigned')),
                    'actor_name' => $actor['name'],
                    'actor_type' => $actor['type'],
                    'icon' => 'ri-user-shared-line',
                    'color' => 'warning',
                    'badge' => 'Driver Changed',
                    'time' => $time,
                    'changes' => $changes,
                    'duration' => null,
                ];
            }

            if ($oldDriverId && !$newDriverId) {
                return [
                    'id' => $log->id,
                    'event_key' => 'unassigned',
                    'title' => 'Driver unassigned',
                    'description' => $fromName,
                    'actor_name' => $actor['name'],
                    'actor_type' => $actor['type'],
                    'icon' => 'ri-user-unfollow-line',
                    'color' => 'secondary',
                    'badge' => 'Driver Unassigned',
                    'time' => $time,
                    'changes' => $changes,
                    'duration' => null,
                ];
            }
        }

        // Status-only to assigned without driver_id in payload
        if ($newStatus === TripCrew::STATUS_ASSIGNED && $oldStatus === TripCrew::STATUS_UNASSIGNED) {
            return [
                'id' => $log->id,
                'event_key' => 'assigned',
                'title' => 'Driver assigned',
                'description' => $trip->driver?->name,
                'actor_name' => $actor['name'],
                'actor_type' => $actor['type'],
                'icon' => 'ri-user-follow-line',
                'color' => 'primary',
                'badge' => 'Driver Assigned',
                'time' => $time,
                'changes' => $changes,
                'duration' => null,
            ];
        }

        if (!empty($changes)) {
            return [
                'id' => $log->id,
                'event_key' => 'updated',
                'title' => 'Schedule updated',
                'description' => null,
                'actor_name' => $actor['name'],
                'actor_type' => $actor['type'],
                'icon' => 'ri-edit-line',
                'color' => 'warning',
                'badge' => 'Schedule Updated',
                'time' => $time,
                'changes' => $changes,
                'duration' => null,
            ];
        }

        // Legacy logs with description but no structured values
        $cleaned = $this->cleanLegacyDescription($log->description);
        if ($cleaned) {
            return [
                'id' => $log->id,
                'event_key' => 'legacy',
                'title' => $cleaned,
                'description' => null,
                'actor_name' => $actor['name'],
                'actor_type' => $actor['type'],
                'icon' => 'ri-history-line',
                'color' => 'secondary',
                'badge' => 'Activity',
                'time' => $time,
                'changes' => [],
                'duration' => null,
            ];
        }

        return null;
    }

    protected function resolveAssignment(Trip $trip, Collection $logs): array
    {
        $createdLog = $logs->firstWhere('action', 'created');
        $createdValues = $createdLog?->new_values ?? [];
        $createdWithDriver = !empty($createdValues['driver_id']);

        $latestAssignment = null;
        $latestDriverId = null;

        foreach ($logs as $log) {
            $old = $log->old_values ?? [];
            $new = $log->new_values ?? [];

            if (!array_key_exists('driver_id', $new)) {
                continue;
            }

            $oldDriver = $old['driver_id'] ?? null;
            $newDriver = $new['driver_id'] ?? null;

            if ($newDriver && (string) $oldDriver !== (string) $newDriver) {
                $latestAssignment = Carbon::parse($log->created_at);
                $latestDriverId = $newDriver;
            }
        }

        if ($latestAssignment) {
            return [
                'time' => $latestAssignment,
                'driver_name' => $this->driverName($latestDriverId) ?? $trip->driver?->name,
            ];
        }

        if ($createdWithDriver) {
            return [
                'time' => Carbon::parse($createdLog->created_at ?? $trip->created_at),
                'driver_name' => $this->driverName($createdValues['driver_id']) ?? $trip->driver?->name,
            ];
        }

        if ($trip->driver_id && !$this->hadUnassignedState($logs)) {
            // Likely created assigned but create log missing driver_id payload
            return [
                'time' => Carbon::parse($trip->created_at),
                'driver_name' => $trip->driver?->name,
            ];
        }

        return [
            'time' => null,
            'driver_name' => $trip->driver?->name,
        ];
    }

    protected function hadUnassignedState(Collection $logs): bool
    {
        foreach ($logs as $log) {
            $old = $log->old_values ?? [];
            $new = $log->new_values ?? [];
            if (($old['status'] ?? null) === TripCrew::STATUS_UNASSIGNED
                || (($new['status'] ?? null) === TripCrew::STATUS_UNASSIGNED)
                || (array_key_exists('driver_id', $old) && empty($old['driver_id']))
            ) {
                return true;
            }
        }

        return false;
    }

    protected function firstStatusTransition(Collection $logs, string $toStatus): ?ActivityLog
    {
        foreach ($logs as $log) {
            $new = $log->new_values ?? [];
            if (($new['status'] ?? null) === $toStatus) {
                return $log;
            }
        }

        return null;
    }

    protected function friendlyChanges(array $old, array $new): array
    {
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $changes = [];

        foreach ($keys as $key) {
            if (in_array($key, $this->hiddenFields, true)) {
                continue;
            }

            $oldRaw = $old[$key] ?? null;
            $newRaw = $new[$key] ?? null;

            if ($this->valuesEqual($oldRaw, $newRaw)) {
                continue;
            }

            $changes[] = [
                'label' => $this->fieldLabel($key),
                'old' => $this->formatFieldValue($key, $oldRaw),
                'new' => $this->formatFieldValue($key, $newRaw),
            ];
        }

        return $changes;
    }

    protected function statusOnlyChanges(array $changes): array
    {
        return array_values(array_filter($changes, fn ($change) => ($change['label'] ?? '') === 'Status'));
    }

    protected function fieldLabel(string $key): string
    {
        return match ($key) {
            'driver_id' => 'Driver',
            'partner_id' => 'Partner',
            'trip_date' => 'Trip Date',
            'title' => 'Trip Title',
            'status' => 'Status',
            default => ucwords(str_replace('_', ' ', $key)),
        };
    }

    protected function formatFieldValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_array($value)) {
            return '—';
        }

        return match ($key) {
            'driver_id' => $this->driverName($value) ?? '—',
            'partner_id' => $this->partnerName($value) ?? '—',
            'status' => $this->statusLabel((string) $value),
            'trip_date' => Carbon::parse($value)->format('M j, Y'),
            default => (string) $value,
        };
    }

    protected function statusLabel(?string $status): string
    {
        if (!$status) {
            return 'Unknown';
        }

        return TripCrew::getStatuses()[$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    protected function driverName(mixed $id): ?string
    {
        if (!$id) {
            return null;
        }

        return Driver::find($id)?->name;
    }

    protected function partnerName(mixed $id): ?string
    {
        if (!$id) {
            return null;
        }

        return Partner::find($id)?->title;
    }

    protected function actorFromLog(?ActivityLog $log): array
    {
        if (!$log) {
            return ['name' => 'System', 'type' => 'System'];
        }

        if ($log->user) {
            return ['name' => $log->user->name, 'type' => 'Admin'];
        }

        if ($log->driver) {
            return ['name' => $log->driver->name, 'type' => 'Driver'];
        }

        return ['name' => 'System', 'type' => 'System'];
    }

    protected function cleanLegacyDescription(?string $description): ?string
    {
        if (!$description) {
            return null;
        }

        $text = preg_replace('/Trip\s*#\d+\s*/i', 'Trip ', $description);
        $text = preg_replace("/\s+/", ' ', trim($text ?? ''));

        // Soften common technical phrases without aggressive parsing
        $replacements = [
            'status changed from \'assigned\' to \'in_progress\'' => 'Trip started',
            'status changed from "assigned" to "in_progress"' => 'Trip started',
            'status changed from \'in_progress\' to \'completed\'' => 'Trip completed',
            'status changed from "in_progress" to "completed"' => 'Trip completed',
            'status changed from \'assigned\' to \'cancelled\'' => 'Trip cancelled',
            'status changed from \'in_progress\' to \'cancelled\'' => 'Trip cancelled',
            'Trip created without driver (unassigned)' => 'Schedule created',
            'Trip created without driver' => 'Schedule created',
        ];

        foreach ($replacements as $from => $to) {
            if (stripos($text, $from) !== false) {
                return $to;
            }
        }

        if (preg_match('/created for driver/i', $text)) {
            return 'Schedule created';
        }

        if (preg_match('/status changed from .+ to in_progress/i', $text)) {
            return 'Trip started';
        }

        if (preg_match('/status changed from .+ to completed/i', $text)) {
            return 'Trip completed';
        }

        if (preg_match('/status changed from .+ to cancelled/i', $text)) {
            return 'Trip cancelled';
        }

        return $text !== '' ? $text : null;
    }

    protected function formatDuration(Carbon $start, Carbon $end): string
    {
        $totalMinutes = max(0, (int) $start->diffInMinutes($end));
        $hours = intdiv($totalMinutes, 60);
        $mins = $totalMinutes % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours === 1 ? 'hr' : 'hrs');
        }

        if ($mins > 0 || empty($parts)) {
            $parts[] = $mins . ' ' . ($mins === 1 ? 'min' : 'mins');
        }

        return implode(' ', $parts);
    }

    protected function valuesEqual(mixed $a, mixed $b): bool
    {
        if (is_array($a) || is_array($b)) {
            return $a == $b;
        }

        return (string) $a === (string) $b;
    }
}
