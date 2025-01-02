<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Models\PermissionRequest;
use Illuminate\Support\Facades\Log;

class ManagerPermissionNotificationService
{
    public function notifyEmployee(PermissionRequest $request, string $type, string $message, bool $isHRNotification = false): void
    {
        try {
            // التحقق من عدم وجود إشعار مكرر
            $existingNotification = Notification::where([
                'user_id' => $request->user_id,
                'type' => $type,
                'related_id' => $request->id
            ])->exists();

            if (!$existingNotification) {
                $this->createNotification($request->user, $request, $type, $message, $isHRNotification);
                Log::info('Notification sent to employee: ' . $request->user_id);
            }
        } catch (\Exception $e) {
            Log::error('Error in notifyEmployee: ' . $e->getMessage());
        }
    }

    private function createNotification(User $user, PermissionRequest $request, string $type, string $message, bool $isHRNotification): void
    {
        try {
            Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'data' => [
                    'message' => $message,
                    'request_id' => $request->id,
                    'employee_name' => $request->user->name,
                    'departure_time' => $request->departure_time->format('Y-m-d H:i'),
                    'return_time' => $request->return_time->format('Y-m-d H:i'),
                    'minutes_used' => $request->minutes_used,
                    'reason' => $request->reason,
                    'remaining_minutes' => $request->remaining_minutes,
                    'is_hr_notification' => $isHRNotification,
                    'is_manager_notification' => !$isHRNotification,
                    'is_team_owner' => !$isHRNotification
                ],
                'related_id' => $request->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error in createNotification: ' . $e->getMessage());
        }
    }
}
