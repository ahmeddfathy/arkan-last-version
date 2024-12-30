<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class  BroadNotificationController extends Controller
{
    // Create a new notification
    public function create(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'data' => 'required|array',
            'related_id' => 'nullable|integer',
        ]);

        $notification = Notification::create($validated);

        return response()->json(['message' => 'Notification created successfully!', 'notification' => $notification]);
    }

    // Get all notifications
    public function index()
    {
        $notifications = Notification::with('user')->get();

        return response()->json($notifications);
    }

    // Mark a notification as read
    public function markAsRead(Notification $notification)
    {
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read!', 'notification' => $notification]);
    }

    // Delete a notification
    public function delete(Notification $notification)
    {
        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully!']);
    }

    // Notify all users
    public function notifyAll(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'data' => 'required|array',
            'related_id' => 'nullable|integer',
        ]);

        $users = User::all();

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => $validated['type'],
                'data' => $validated['data'],
                'related_id' => $validated['related_id'] ?? null,
            ]);
        }

        return response()->json(['message' => 'Notification sent to all users!']);
    }
}

// Web routes in web.php
// use App\Http\Controllers\NotificationController;

// Route::prefix('notifications')->group(function () {
//     Route::get('/', [NotificationController::class, 'index']);
//     Route::post('/', [NotificationController::class, 'create']);
//     Route::post('/notify-all', [NotificationController::class, 'notifyAll']);
//     Route::patch('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
//     Route::delete('/{notification}', [NotificationController::class, 'delete']);
// });
