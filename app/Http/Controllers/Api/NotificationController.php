<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated driver.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $driver = $request->user();
        
        $perPage = $request->get('per_page', 20);
        $notifications = $driver->notifications()
            ->latest()
            ->paginate($perPage);

        $formattedNotifications = $notifications->map(function ($notification) {
            return $this->formatNotification($notification);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $formattedNotifications,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
            ],
        ], 200);
    }

    /**
     * Get unread notifications count.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount(Request $request)
    {
        $driver = $request->user();
        $count = $driver->notifications()->where('is_read', false)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
            ],
        ], 200);
    }

    /**
     * Mark a specific notification as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, $id)
    {
        $driver = $request->user();
        
        $notification = $driver->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found or you do not have access to this notification.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data' => $this->formatNotification($notification),
        ], 200);
    }

    /**
     * Mark all notifications as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead(Request $request)
    {
        $driver = $request->user();
        
        $updated = $driver->notifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
            'data' => [
                'updated_count' => $updated,
            ],
        ], 200);
    }

    /**
     * Format notification for API response.
     *
     * @param  \App\Models\Notification  $notification
     * @return array
     */
    protected function formatNotification(Notification $notification): array
    {
        $icon = $this->getNotificationIcon($notification->type);
        $timeAgo = $this->getTimeAgo($notification->created_at);

        return [
            'id' => $notification->id,
            'icon' => $icon,
            'title' => $notification->title,
            'message' => $notification->message,
            'type' => $notification->type,
            'is_read' => $notification->is_read,
            'read_at' => $notification->read_at ? $notification->read_at->toISOString() : null,
            'timestamp' => [
                'datetime' => $notification->created_at->toISOString(),
                'formatted' => $notification->created_at->format('M d, Y h:i A'),
                'time_ago' => $timeAgo,
            ],
            'created_at' => $notification->created_at->toISOString(),
        ];
    }

    /**
     * Get icon name based on notification type.
     *
     * @param  string  $type
     * @return string
     */
    protected function getNotificationIcon(string $type): string
    {
        return match($type) {
            'success' => 'check-circle',
            'warning' => 'alert-triangle',
            'danger' => 'x-circle',
            'info' => 'info',
            default => 'bell',
        };
    }

    /**
     * Get human-readable time ago string.
     *
     * @param  \Carbon\Carbon  $date
     * @return string
     */
    protected function getTimeAgo(Carbon $date): string
    {
        $now = Carbon::now();
        $diffInMinutes = $now->diffInMinutes($date);
        $diffInHours = $now->diffInHours($date);
        $diffInDays = $now->diffInDays($date);

        if ($diffInMinutes < 1) {
            return 'Just now';
        } elseif ($diffInMinutes < 60) {
            return $diffInMinutes . ' ' . ($diffInMinutes === 1 ? 'minute' : 'minutes') . ' ago';
        } elseif ($diffInHours < 24) {
            return $diffInHours . ' ' . ($diffInHours === 1 ? 'hour' : 'hours') . ' ago';
        } elseif ($diffInDays === 1) {
            return 'Yesterday';
        } elseif ($diffInDays < 7) {
            return $diffInDays . ' days ago';
        } elseif ($diffInDays < 30) {
            $weeks = floor($diffInDays / 7);
            return $weeks . ' ' . ($weeks === 1 ? 'week' : 'weeks') . ' ago';
        } elseif ($diffInDays < 365) {
            $months = floor($diffInDays / 30);
            return $months . ' ' . ($months === 1 ? 'month' : 'months') . ' ago';
        } else {
            $years = floor($diffInDays / 365);
            return $years . ' ' . ($years === 1 ? 'year' : 'years') . ' ago';
        }
    }
}
