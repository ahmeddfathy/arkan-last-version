<?php

namespace App\Services\Notifications;

use App\Models\OverTimeRequests;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;

class OvertimeManagerNotificationService
{
    public function notifyAboutNewRequest(OverTimeRequests $request): void
    {
        $overtimeDate = Carbon::parse($request->overtime_date);

        User::where('role', 'manager')->each(function ($manager) use ($request, $overtimeDate) {
            Notification::create([
                'user_id' => $manager->id,
                'type' => 'overtime-requests',
                'data' => [
                    'message' => "{$request->user->name} has submitted an overtime request",
                    'request_id' => $request->id,
                    'date' => $overtimeDate->format('Y-m-d'),
                ],
                'related_id' => $request->id
            ]);
        });
    }

    public function notifyAboutModification(OverTimeRequests $request): void
    {
        $overtimeDate = Carbon::parse($request->overtime_date);

        User::where('role', 'manager')->each(function ($manager) use ($request, $overtimeDate) {
            Notification::create([
                'user_id' => $manager->id,
                'type' => 'overtime-requests',
                'data' => [
                    'message' => "{$request->user->name} has modified their overtime request",
                    'request_id' => $request->id,
                    'date' => $overtimeDate->format('Y-m-d'),
                ],
                'related_id' => $request->id
            ]);
        });
    }

    public function notifyAboutDeletion(OverTimeRequests $request): void
    {
        User::where('role', 'manager')->each(function ($manager) use ($request) {
            Notification::create([
                'user_id' => $manager->id,
                'type' => 'overtime-requests',
                'data' => [
                    'message' => "{$request->user->name} has deleted their overtime request",
                    'request_id' => $request->id,
                    'date' => $request->overtime_date,
                ],
                'related_id' => $request->id
            ]);
        });
    }
}
