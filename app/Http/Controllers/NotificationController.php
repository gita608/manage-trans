<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Driver;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function index()
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function getUnreadCount()
    {
        $count = Cache::remember(
            'notifications.unread.' . Auth::id(),
            300, // 5 minutes
            fn() => Auth::user()->notifications()->where('is_read', false)->count()
        );
        return response()->json(['count' => $count]);
    }

    public function getRecent()
    {
        $notifications = Auth::user()->notifications()->latest()->limit(5)->get();
        return response()->json(['notifications' => $notifications]);
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        Cache::forget('notifications.unread.' . Auth::id());
        return redirect()->back()->with('success', 'Notification marked as read');
    }

    public function markAllAsRead()
    {
        Auth::user()->notifications()->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        Cache::forget('notifications.unread.' . Auth::id());
        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    /**
     * Show the admin list of all driver notifications.
     */
    public function adminIndex()
    {
        $notifications = Notification::whereNotNull('driver_id')
            ->whereDate('created_at', today())
            ->with(['driver', 'user'])
            ->latest('created_at')
            ->get();
        return view('notifications.admin-index', compact('notifications'));
    }

    /**
     * Show the form for creating a new notification.
     */
    public function create()
    {
        $drivers = Driver::orderBy('name')->get();
        return view('notifications.create', compact('drivers'));
    }

    /**
     * Store a newly created notification.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $logContext = [
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'title' => $validated['title'],
            'message_preview' => substr($validated['message'], 0, 100),
            'target' => empty($validated['driver_id']) ? 'all_drivers' : 'specific_driver',
        ];

        Log::info('Notification creation started', $logContext);

        $pushSent = 0;
        $pushFailed = 0;
        $driversWithoutToken = 0;

        // If driver_id is null, send to all drivers
        if (empty($validated['driver_id'])) {
            // Get all drivers
            $drivers = Driver::all();
            
            Log::info('Sending notification to all drivers', array_merge($logContext, [
                'total_drivers' => $drivers->count(),
            ]));
            
            // Create notification for each driver and send push notification
            foreach ($drivers as $driver) {
                // Create database notification
                $notification = Notification::create([
                    'user_id' => Auth::id(),
                    'driver_id' => $driver->id,
                    'title' => $validated['title'],
                    'message' => $validated['message'],
                ]);

                Log::debug('Database notification created', [
                    'notification_id' => $notification->id,
                    'driver_id' => $driver->id,
                    'driver_name' => $driver->name,
                ]);

                // Check if driver has notification token
                if (!$driver->notification_token) {
                    $driversWithoutToken++;
                    Log::warning('Driver has no notification token, skipping push', [
                        'driver_id' => $driver->id,
                        'driver_name' => $driver->name,
                    ]);
                    continue;
                }

                // Send push notification
                if ($this->firebaseService->sendToDriver($driver, $validated['title'], $validated['message'])) {
                    $pushSent++;
                    Log::info('Push notification sent successfully', [
                        'driver_id' => $driver->id,
                        'driver_name' => $driver->name,
                    ]);
                } else {
                    $pushFailed++;
                    Log::error('Push notification failed', [
                        'driver_id' => $driver->id,
                        'driver_name' => $driver->name,
                    ]);
                }
            }
            
            Log::info('Notification sending completed for all drivers', array_merge($logContext, [
                'total_drivers' => $drivers->count(),
                'push_sent' => $pushSent,
                'push_failed' => $pushFailed,
                'drivers_without_token' => $driversWithoutToken,
            ]));
            
            $message = "Notification sent to all {$drivers->count()} drivers successfully!";
            if ($pushFailed > 0 || $driversWithoutToken > 0) {
                $details = [];
                if ($pushSent > 0) $details[] = "{$pushSent} push notifications sent";
                if ($pushFailed > 0) $details[] = "{$pushFailed} failed";
                if ($driversWithoutToken > 0) $details[] = "{$driversWithoutToken} without token";
                $message .= " (" . implode(", ", $details) . ")";
            }
        } else {
            // Send to specific driver
            $driver = Driver::find($validated['driver_id']);
            
            Log::info('Sending notification to specific driver', array_merge($logContext, [
                'driver_id' => $driver->id,
                'driver_name' => $driver->name,
                'has_notification_token' => !empty($driver->notification_token),
            ]));
            
            // Create database notification
            $notification = Notification::create([
                'user_id' => Auth::id(),
                'driver_id' => $validated['driver_id'],
                'title' => $validated['title'],
                'message' => $validated['message'],
            ]);

            Log::debug('Database notification created', [
                'notification_id' => $notification->id,
                'driver_id' => $driver->id,
            ]);
            
            // Send push notification
            $pushResult = $this->firebaseService->sendToDriver($driver, $validated['title'], $validated['message']);
            
            if ($pushResult) {
                $pushSent = 1;
                Log::info('Notification sent successfully to specific driver', array_merge($logContext, [
                    'driver_id' => $driver->id,
                    'push_status' => 'success',
                ]));
                $message = "Notification sent to {$driver->name} successfully!";
            } else {
                $pushFailed = 1;
                Log::error('Notification failed to send to specific driver', array_merge($logContext, [
                    'driver_id' => $driver->id,
                    'push_status' => 'failed',
                ]));
                $message = "Notification created for {$driver->name}, but push notification failed. Check logs for details.";
            }
        }

        return redirect()->route('notifications.admin-index')->with('success', $message);
    }
}
