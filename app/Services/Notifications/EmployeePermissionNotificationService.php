<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\PermissionRequest;
use App\Models\User;

class EmployeePermissionNotificationService
{
    public function notifyEmployee(PermissionRequest $request, string $type, array $data): void
    {
        $notificationData = array_merge($data, [
            'request_details' => [
                'departure_time' => $request->departure_time->format('Y-m-d H:i'),
                'return_time' => $request->return_time->format('Y-m-d H:i'),
                'minutes_used' => $request->minutes_used,
            ]
        ]);

        Notification::create([
            'user_id' => $request->user_id,
            'type' => $type,
            'data' => $notificationData,
            'related_id' => $request->id
        ]);
    }

    public function notifyTeamMembers(PermissionRequest $request, string $type, array $data): void
    {
        if ($request->user && $request->user->currentTeam) {
            $teamMembers = $request->user->currentTeam->users()
                ->where('users.id', '!=', $request->user_id)
                ->get();

            foreach ($teamMembers as $member) {
                Notification::create([
                    'user_id' => $member->id,
                    'type' => $type,
                    'data' => $data,
                    'related_id' => $request->id
                ]);
            }
        }
    }

    public function deleteExistingNotifications(PermissionRequest $request, string $type): void
    {
        Notification::where('related_id', $request->id)
            ->where('type', $type)
            ->delete();
    }
}
