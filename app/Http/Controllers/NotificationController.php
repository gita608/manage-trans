<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
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
}
