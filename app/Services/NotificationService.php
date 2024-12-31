<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\AbsenceRequest;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function createLeaveRequestNotification(AbsenceRequest $request): void
    {
        // نجلب المديرين المباشرين (admin/owner في الفريق)
        $teamAdmins = DB::table('team_user')
            ->where('team_id', $request->user->team_id)
            ->where(function ($query) {
                $query->where('role', 'admin')
                    ->orWhere('role', 'owner');
            })
            ->pluck('user_id');

        // نجلب HR
        $hrUsers = User::role('hr')->pluck('id');

        // نجمع كل المستخدمين الذين سيتلقون الإشعار
        $notifyUsers = User::whereIn('id', $teamAdmins)
            ->orWhereIn('id', $hrUsers)
            ->get();

        foreach ($notifyUsers as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'new_leave_request',
                'data' => [
                    'message' => "{$request->user->name} قام بتقديم طلب غياب",
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                ],
                'related_id' => $request->id
            ]);
        }
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
        // حذف الإشعارات السابقة
        Notification::where('related_id', $request->id)
            ->where('type', 'leave_request_modified')
            ->delete();

        // نجلب المديرين المباشرين وHR
        $teamAdmins = DB::table('team_user')
            ->where('team_id', $request->user->team_id)
            ->where(function ($query) {
                $query->where('role', 'admin')
                    ->orWhere('role', 'owner');
            })
            ->pluck('user_id');

        $hrUsers = User::role('hr')->pluck('id');

        $notifyUsers = User::whereIn('id', $teamAdmins)
            ->orWhereIn('id', $hrUsers)
            ->get();

        foreach ($notifyUsers as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'leave_request_modified',
                'data' => [
                    'message' => "{$request->user->name} قام بتعديل طلب الغياب",
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                ],
                'related_id' => $request->id
            ]);
        }
    }

    public function notifyRequestDeleted(AbsenceRequest $request): void
    {
        // نجلب المديرين المباشرين وHR
        $teamAdmins = DB::table('team_user')
            ->where('team_id', $request->user->team_id)
            ->where(function ($query) {
                $query->where('role', 'admin')
                    ->orWhere('role', 'owner');
            })
            ->pluck('user_id');

        $hrUsers = User::role('hr')->pluck('id');

        $notifyUsers = User::whereIn('id', $teamAdmins)
            ->orWhereIn('id', $hrUsers)
            ->get();

        foreach ($notifyUsers as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'leave_request_deleted',
                'data' => [
                    'message' => "{$request->user->name} قام بحذف طلب الغياب",
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                ],
                'related_id' => $request->id
            ]);
        }
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
