<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\AbsenceRequest;

class NotificationService
{
    public function createLeaveRequestNotification(AbsenceRequest $request): void
    {
        User::where('role', 'manager')->each(function ($manager) use ($request) {
            Notification::create([
                'user_id' => $manager->id,
                'type' => 'new_leave_request',
                'data' => [
                    'message' => "{$request->user->name} has submitted a leave request",
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                ],
                'related_id' => $request->id
            ]);
        });
    }

    public function createStatusUpdateNotification(AbsenceRequest $request): void
    {
        // Delete any existing status notifications for this request
        $this->deleteStatusNotifications($request);

        // Create new notification
        Notification::create([
            'user_id' => $request->user_id,
            'type' => 'leave_request_status_update',
            'data' => [
                'message' => "Your leave request has been {$request->status}",
                'request_id' => $request->id,
                'status' => $request->status,
                'rejection_reason' => $request->rejection_reason,
            ],
            'related_id' => $request->id
        ]);
    }

    public function deleteStatusNotifications(AbsenceRequest $request): void
    {
        Notification::where('related_id', $request->id)
            ->where('type', 'leave_request_status_update')
            ->delete();
    }

    public function notifyRequestModified(AbsenceRequest $request): void
    {
        // Delete existing notifications for this request modification
        Notification::where('related_id', $request->id)
            ->where('type', 'leave_request_modified')
            ->delete();

        User::where('role', 'manager')->each(function ($manager) use ($request) {
            Notification::create([
                'user_id' => $manager->id,
                'type' => 'leave_request_modified',
                'data' => [
                    'message' => "{$request->user->name} has modified their leave request",
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                ],
                'related_id' => $request->id
            ]);
        });
    }

    public function notifyRequestDeleted(AbsenceRequest $request): void
    {
        User::where('role', 'manager')->each(function ($manager) use ($request) {
            Notification::create([
                'user_id' => $manager->id,
                'type' => 'leave_request_deleted',
                'data' => [
                    'message' => "{$request->user->name} has deleted their leave request",
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                ],
                'related_id' => $request->id
            ]);
        });
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
