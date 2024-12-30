<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class SalaryNotificationService
{
    /**
     * Create a salary sheet notification for a user.
     *
     * @param User $user
     * @param array $salarySheetData
     * @return void
     */
    public function createSalarySheetNotification(User $user, array $salarySheetData): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => 'salary_sheet_uploaded',
            'data' => [
                'message' => "Salary sheet for {$salarySheetData['month']} has been uploaded.",
                'month' => $salarySheetData['month'],
                'file_name' => $salarySheetData['filename'],
            ],
            'related_id' => $salarySheetData['id']
        ]);
    }

    /**
     * Get all unread notifications for a specific user.
     *
     * @param int $userId
     * @return array
     */
    public function getUserUnreadNotifications(int $userId): array
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at') // Only unread notifications
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Mark a notification as read by its ID.
     *
     * @param int $notificationId
     * @return void
     */
    public function markAsRead(int $notificationId): void
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->markAsRead();
    }

    /**
     * Count unread notifications for a specific user.
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
