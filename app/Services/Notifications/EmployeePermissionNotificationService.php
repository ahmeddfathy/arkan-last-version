<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\PermissionRequest;

class EmployeePermissionNotificationService
{
    public function notifyEmployee(PermissionRequest $request, string $type, array $data): void
    {
        Notification::create([
            'user_id' => $request->user_id,
            'type' => $type,
            'data' => $data,
            'related_id' => $request->id
        ]);
    }

    public function deleteExistingNotifications(PermissionRequest $request, string $type): void
    {
        Notification::where('related_id', $request->id)
            ->where('type', $type)
            ->delete();
    }
}
