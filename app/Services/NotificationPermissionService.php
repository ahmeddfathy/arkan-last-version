<?php

namespace App\Services;

use App\Models\PermissionRequest;
use App\Services\Notifications\ManagerPermissionNotificationService;
use App\Services\Notifications\EmployeePermissionNotificationService;

class NotificationPermissionService
{
    protected $managerNotificationService;
    protected $employeeNotificationService;

    public function __construct(
        ManagerPermissionNotificationService $managerNotificationService,
        EmployeePermissionNotificationService $employeeNotificationService
    ) {
        $this->managerNotificationService = $managerNotificationService;
        $this->employeeNotificationService = $employeeNotificationService;
    }

    // Employee Actions Notifications
    public function createPermissionRequestNotification(PermissionRequest $request): void
    {
        $message = "{$request->user->name} has submitted a permission request";
        $this->managerNotificationService->notifyAllManagers($request, 'new_permission_request', $message);
    }

    public function notifyPermissionModified(PermissionRequest $request): void
    {
        $this->employeeNotificationService->deleteExistingNotifications($request, 'permission_request_modified');

        $message = "{$request->user->name} has modified their permission request";
        $this->managerNotificationService->notifyAllManagers($request, 'permission_request_modified', $message);
    }

    public function notifyPermissionDeleted(PermissionRequest $request): void
    {
        $message = "{$request->user->name} has deleted their permission request";
        $this->managerNotificationService->notifyAllManagers($request, 'permission_request_deleted', $message);
    }

    // Manager Actions Notifications
    public function createPermissionStatusUpdateNotification(PermissionRequest $request): void
    {
        $this->employeeNotificationService->deleteExistingNotifications($request, 'permission_request_status_update');

        $data = [
            'message' => "Your permission request has been {$request->status}",
            'request_id' => $request->id,
            'status' => $request->status,
            'rejection_reason' => $request->rejection_reason,
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'permission_request_status_update', $data);
    }

    public function notifyManagerResponseModified(PermissionRequest $request): void
    {
        $data = [
            'message' => "The response to your permission request has been modified",
            'request_id' => $request->id,
            'status' => $request->status,
            'rejection_reason' => $request->rejection_reason,
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'manager_response_modified', $data);
    }

    public function notifyManagerResponseDeleted(PermissionRequest $request): void
    {
        $data = [
            'message' => "The response to your permission request has been removed",
            'request_id' => $request->id
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'manager_response_deleted', $data);
    }
}

