<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\OverTimeRequests;
use Carbon\Carbon;

class NotificationOvertimeService
{
    public function createOvertimeRequestNotification(OverTimeRequests $request): void
    {
        $overtimeDate = Carbon::parse($request->overtime_date);

        User::where('role', 'manager')->each(function ($manager) use ($request, $overtimeDate) {
            Notification::create([
                'user_id' => $manager->id,
                'type' => 'overtime-requests',
                'data' => [
                    'message' => "{$request->user->name} has submitted an overtime request",
                    'request_id' => $request->id,
                    'date' => $overtimeDate->format('Y-m-d'),
                ],
                'related_id' => $request->id
            ]);
        });
    }

    public function createStatusUpdateNotification(OverTimeRequests $request): void
    {
        // Delete existing status notifications first
        $this->deleteStatusNotifications($request);

        // Create new notification
        Notification::create([
            'user_id' => $request->user_id,
            'type' => 'overtime-requests',
            'data' => [
                'message' => "Your overtime request has been {$request->status}",
                'request_id' => $request->id,
                'status' => $request->status,
                'rejection_reason' => $request->rejection_reason,
            ],
            'related_id' => $request->id
        ]);
    }

    public function deleteStatusNotifications(OverTimeRequests $request): void
    {
        Notification::where('related_id', $request->id)
            ->where('type', 'overtime_request_status_update')
            ->delete();
    }

    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function getUserNotifications(User $user)
    {
        return Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }
}
