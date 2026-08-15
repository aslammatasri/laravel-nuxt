<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // GET /api/notifications
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->notifications()->latest()->paginate(15)
        );
    }

    // GET /api/notifications/unread-count
    public function unreadCount(Request $request)
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    // PATCH /api/notifications/{id}/read
    public function markAsRead(string $id, Request $request)
    {
        $notification = $request->user()->notifications()->find($id);

        if (! $notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json($notification);
    }

    // PATCH /api/notifications/read-all
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->each->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
