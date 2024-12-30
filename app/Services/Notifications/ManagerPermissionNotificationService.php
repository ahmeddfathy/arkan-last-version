<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Models\PermissionRequest;

class ManagerPermissionNotificationService
{
    public function notifyAllManagers(PermissionRequest $request, string $type, string $message): void
    {
        User::where('role', 'manager')->each(function ($manager) use ($request, $type, $message) {
            Notification::create([
                'user_id' => $manager->id,
                'type' => $type,
                'data' => [
                    'message' => $message,
                    'request_id' => $request->id,
                    'date' => $request->permission_date ? $request->permission_date->format('Y-m-d') : null,
                ],
                'related_id' => $request->id
            ]);
        });
    }
}
