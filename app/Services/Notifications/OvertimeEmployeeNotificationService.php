<?php

namespace App\Services\Notifications;

use App\Models\OverTimeRequests;
use App\Models\Notification;

class OvertimeEmployeeNotificationService
{
    public function notifyStatusUpdate(OverTimeRequests $request): void
    {
        $this->deleteExistingStatusNotifications($request);

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

    public function notifyRequestReset(OverTimeRequests $request): void
    {
        $this->deleteExistingStatusNotifications($request);


    }

    public function deleteExistingStatusNotifications(OverTimeRequests $request): void
    {
        Notification::where('related_id', $request->id)
            ->where('type', 'overtime-requests')
            ->delete();
    }
}
