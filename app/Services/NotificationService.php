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
        $notifyUsers = collect();

        // التحقق مما إذا كان المستخدم في فريق
        $hasTeam = DB::table('team_user')
            ->where('user_id', $request->user_id)
            ->exists();

        if ($hasTeam) {
            // نجلب owner الفريق فقط
            $teamOwners = DB::table('team_user')
                ->join('teams', 'teams.id', '=', 'team_user.team_id')
                ->where('team_user.user_id', $request->user_id)
                ->where('team_user.role', 'owner')
                ->pluck('user_id');

            $notifyUsers = $notifyUsers->merge(User::whereIn('id', $teamOwners)->get());
        }

        // نجلب HR دائماً بغض النظر عن وجود المستخدم في فريق
        $hrUsers = User::role('hr')->pluck('id');
        $notifyUsers = $notifyUsers->merge(User::whereIn('id', $hrUsers)->get());

        foreach ($notifyUsers as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'new_leave_request',
                'data' => [
                    'message' => "{$request->user->name} قام بتقديم طلب غياب",
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                    'has_team' => $hasTeam
                ],
                'related_id' => $request->id
            ]);
        }
    }

    public function createStatusUpdateNotification(AbsenceRequest $request): void
    {
        $notifyUsers = collect();
        $updatedBy = auth()->user();
        $currentUserRole = $updatedBy->roles->first()->name ?? null;

        // التحقق مما إذا كان المستخدم في فريق
        $hasTeam = DB::table('team_user')
            ->where('user_id', $request->user_id)
            ->exists();

        // إشعار للموظف صاحب الطلب
        Notification::create([
            'user_id' => $request->user_id,
            'type' => 'leave_request_status_update',
            'data' => [
                'message' => $currentUserRole === 'hr'
                    ? "HR قام بـ " . ($request->hr_status === 'approved' ? 'الموافقة على' : 'رفض') . " طلب الغياب"
                    : "المدير قام بـ " . ($request->manager_status === 'approved' ? 'الموافقة على' : 'رفض') . " طلب الغياب",
                'request_id' => $request->id,
                'date' => $request->absence_date->format('Y-m-d'),
                'has_team' => $hasTeam
            ],
            'related_id' => $request->id
        ]);

        // إذا كان المحدث HR، نرسل إشعار للمدير
        if ($hasTeam && $currentUserRole === 'hr') {
            $teamOwners = DB::table('team_user')
                ->join('teams', 'teams.id', '=', 'team_user.team_id')
                ->where('team_user.user_id', $request->user_id)
                ->where('team_user.role', 'owner')
                ->pluck('user_id');

            $notifyUsers = $notifyUsers->merge(User::whereIn('id', $teamOwners)->get());
        }

        // إذا كان المحدث مدير، نرسل إشعار لل HR
        if (in_array($currentUserRole, ['team_leader', 'department_manager', 'company_manager'])) {
            $hrUsers = User::role('hr')->pluck('id');
            $notifyUsers = $notifyUsers->merge(User::whereIn('id', $hrUsers)->get());
        }

        // إرسال الإشعارات للمستخدمين المحددين
        foreach ($notifyUsers as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'leave_request_response_update',
                'data' => [
                    'message' => $currentUserRole === 'hr'
                        ? "HR قام بالرد على طلب الغياب للموظف {$request->user->name}"
                        : "المدير قام بالرد على طلب الغياب للموظف {$request->user->name}",
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                    'has_team' => $hasTeam
                ],
                'related_id' => $request->id
            ]);
        }
    }

    public function notifyStatusReset(AbsenceRequest $request, string $responseType): void
    {
        $hasTeam = DB::table('team_user')
            ->where('user_id', $request->user_id)
            ->exists();

        // إشعار للموظف
        Notification::create([
            'user_id' => $request->user_id,
            'type' => 'leave_request_status_reset',
            'data' => [
                'message' => ($responseType === 'manager' ? 'المدير' : 'HR') . " قام بإعادة تعيين حالة طلب الغياب",
                'request_id' => $request->id,
                'response_type' => $responseType,
                'has_team' => $hasTeam
            ],
            'related_id' => $request->id
        ]);

        // إشعار للطرف الآخر
        if ($responseType === 'manager' && $hasTeam) {
            $hrUsers = User::role('hr')->pluck('id');
            foreach ($hrUsers as $hrUserId) {
                if ($hrUserId !== auth()->id()) {
                    Notification::create([
                        'user_id' => $hrUserId,
                        'type' => 'leave_request_status_reset',
                        'data' => [
                            'message' => "المدير قام بإعادة تعيين حالة طلب الغياب للموظف {$request->user->name}",
                            'request_id' => $request->id,
                            'response_type' => $responseType,
                            'has_team' => $hasTeam
                        ],
                        'related_id' => $request->id
                    ]);
                }
            }
        } elseif ($responseType === 'hr' && $hasTeam) {
            $teamOwners = DB::table('team_user')
                ->join('teams', 'teams.id', '=', 'team_user.team_id')
                ->where('team_user.user_id', $request->user_id)
                ->where('team_user.role', 'owner')
                ->pluck('user_id');

            foreach ($teamOwners as $ownerId) {
                if ($ownerId !== auth()->id()) {
                    Notification::create([
                        'user_id' => $ownerId,
                        'type' => 'leave_request_status_reset',
                        'data' => [
                            'message' => "HR قام بإعادة تعيين حالة طلب الغياب للموظف {$request->user->name}",
                            'request_id' => $request->id,
                            'response_type' => $responseType,
                            'has_team' => $hasTeam
                        ],
                        'related_id' => $request->id
                    ]);
                }
            }
        }
    }

    public function notifyRequestModified(AbsenceRequest $request): void
    {
        // حذف الإشعارات السابقة
        Notification::where('related_id', $request->id)
            ->where('type', 'leave_request_modified')
            ->delete();

        $notifyUsers = collect();

        // التحقق مما إذا كان المستخدم في فريق
        $hasTeam = DB::table('team_user')
            ->where('user_id', $request->user_id)
            ->exists();

        if ($hasTeam) {
            // نجلب owner الفريق فقط
            $teamOwners = DB::table('team_user')
                ->join('teams', 'teams.id', '=', 'team_user.team_id')
                ->where('team_user.user_id', $request->user_id)
                ->where('team_user.role', 'owner')
                ->pluck('user_id');

            $notifyUsers = $notifyUsers->merge(User::whereIn('id', $teamOwners)->get());
        }

        // نجلب HR دائماً
        $hrUsers = User::role('hr')->pluck('id');
        $notifyUsers = $notifyUsers->merge(User::whereIn('id', $hrUsers)->get());

        foreach ($notifyUsers as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'leave_request_modified',
                'data' => [
                    'message' => "{$request->user->name} قام بتعديل طلب الغياب",
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                    'has_team' => $hasTeam
                ],
                'related_id' => $request->id
            ]);
        }
    }

    public function notifyRequestDeleted(AbsenceRequest $request): void
    {
        $notifyUsers = collect();

        // التحقق مما إذا كان المستخدم في فريق
        $hasTeam = DB::table('team_user')
            ->where('user_id', $request->user_id)
            ->exists();

        if ($hasTeam) {
            // نجلب owner الفريق فقط
            $teamOwners = DB::table('team_user')
                ->join('teams', 'teams.id', '=', 'team_user.team_id')
                ->where('team_user.user_id', $request->user_id)
                ->where('team_user.role', 'owner')
                ->pluck('user_id');

            $notifyUsers = $notifyUsers->merge(User::whereIn('id', $teamOwners)->get());
        }

        // نجلب HR دائماً
        $hrUsers = User::role('hr')->pluck('id');
        $notifyUsers = $notifyUsers->merge(User::whereIn('id', $hrUsers)->get());

        foreach ($notifyUsers as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'leave_request_deleted',
                'data' => [
                    'message' => "{$request->user->name} قام بحذف طلب الغياب",
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                    'has_team' => $hasTeam
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
