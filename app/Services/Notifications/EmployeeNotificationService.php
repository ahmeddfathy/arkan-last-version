<?php

namespace App\Services\Notifications;

use App\Models\AbsenceRequest;
use App\Models\Notification;

class EmployeeNotificationService
{
    public function notifyStatusUpdate(AbsenceRequest $request): void
    {
        $this->deleteExistingStatusNotifications($request);
        
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

    public function deleteExistingStatusNotifications(AbsenceRequest $request): void
    {
        Notification::where('related_id', $request->id)
            ->where('type', 'leave_request_status_update')
            ->delete();
    }
}