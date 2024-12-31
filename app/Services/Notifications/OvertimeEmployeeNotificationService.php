<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\OverTimeRequests;
use App\Models\User;

class OvertimeEmployeeNotificationService
{
    public function notifyStatusUpdate(OverTimeRequests $request): void
    {
        $this->deleteExistingStatusNotifications($request);

        Notification::create([
            'user_id' => $request->user_id,
            'type' => 'overtime_status_update',
            'data' => [
                'message' => $this->getStatusUpdateMessage($request),
                'request_id' => $request->id,
                'overtime_date' => $request->overtime_date,
                'status' => $request->status,
                'manager_status' => $request->manager_status,
                'hr_status' => $request->hr_status,
                'rejection_reason' => $request->status === 'rejected' ?
                    ($request->manager_rejection_reason ?? $request->hr_rejection_reason) : null
            ],
            'related_id' => $request->id
        ]);
    }

    public function notifyRequestReset(OverTimeRequests $request): void
    {
        Notification::create([
            'user_id' => $request->user_id,
            'type' => 'overtime_status_reset',
            'data' => [
                'message' => 'Your overtime request status has been reset to pending',
                'request_id' => $request->id,
                'overtime_date' => $request->overtime_date
            ],
            'related_id' => $request->id
        ]);
    }

    public function deleteExistingStatusNotifications(OverTimeRequests $request): void
    {
        Notification::where('related_id', $request->id)
            ->where('type', 'overtime_status_update')
            ->delete();
    }

    private function getStatusUpdateMessage(OverTimeRequests $request): string
    {
        $statusText = match ($request->status) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            default => 'updated'
        };

        return "Your overtime request has been {$statusText}";
    }
}
