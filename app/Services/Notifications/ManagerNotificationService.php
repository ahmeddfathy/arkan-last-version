<?php

namespace App\Services\Notifications;

use App\Models\AbsenceRequest;
use App\Models\User;
use App\Models\Notification;

class ManagerNotificationService
{
    public function notifyAboutNewRequest(AbsenceRequest $request): void
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

    public function notifyAboutModification(AbsenceRequest $request): void
    {
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

    public function notifyAboutDeletion(AbsenceRequest $request): void
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
}