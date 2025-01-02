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

    // إشعارات إنشاء وتعديل الطلبات
    public function createPermissionRequestNotification(PermissionRequest $request): void
    {
        $message = "قام {$request->user->name} بتقديم طلب استئذان جديد";
        // إشعار المدراء
        $this->managerNotificationService->notifyEmployee($request, 'new_permission_request', $message, false);
        // إشعار HR
        $this->managerNotificationService->notifyEmployee($request, 'new_permission_request', $message, true);

        // إشعار أعضاء الفريق
        $teamMessage = "قام زميلكم {$request->user->name} بتقديم طلب استئذان";
        $this->employeeNotificationService->notifyTeamMembers($request, 'team_member_permission_request', [
            'message' => $teamMessage
        ]);
    }

    public function notifyPermissionModified(PermissionRequest $request): void
    {
        $this->employeeNotificationService->deleteExistingNotifications($request, 'permission_request_modified');

        $message = "قام {$request->user->name} بتعديل طلب الاستئذان";
        $this->managerNotificationService->notifyEmployee($request, 'permission_request_modified', $message, false);
        $this->managerNotificationService->notifyEmployee($request, 'permission_request_modified', $message, true);
    }

    // إشعارات الردود والموافقات
    public function createPermissionStatusUpdateNotification(PermissionRequest $request): void
    {
        $this->employeeNotificationService->deleteExistingNotifications($request, 'permission_request_status_update');

        $statusArabic = [
            'approved' => 'تمت الموافقة على',
            'rejected' => 'تم رفض',
            'pending' => 'في انتظار الرد على'
        ][$request->status];

        $data = [
            'message' => "{$statusArabic} طلب الاستئذان الخاص بك",
            'status' => $request->status,
            'manager_status' => $request->manager_status,
            'hr_status' => $request->hr_status,
            'manager_rejection_reason' => $request->manager_rejection_reason,
            'hr_rejection_reason' => $request->hr_rejection_reason,
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'permission_request_status_update', $data);
    }

    // إشعارات حالة العودة
    public function notifyReturnStatus(PermissionRequest $request): void
    {
        $returnStatus = [
            0 => 'لم يتم تحديد حالة العودة',
            1 => 'عاد في الوقت المحدد',
            2 => 'تأخر عن موعد العودة'
        ][$request->returned_on_time];

        $data = [
            'message' => "تم تحديث حالة العودة: {$returnStatus}",
            'return_status' => $request->returned_on_time
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'return_status_update', $data);
    }

    public function notifyPermissionDeleted(PermissionRequest $request): void
    {
        $message = "قام {$request->user->name} بحذف طلب الاستئذان";
        // إشعار المدراء
        $this->managerNotificationService->notifyEmployee($request, 'permission_request_deleted', $message, false);
        // إشعار HR
        $this->managerNotificationService->notifyEmployee($request, 'permission_request_deleted', $message, true);

        // إشعار أعضاء الفريق
        $teamMessage = "قام زميلكم {$request->user->name} بحذف طلب الاستئذان";
        $this->employeeNotificationService->notifyTeamMembers($request, 'team_member_permission_deleted', [
            'message' => $teamMessage
        ]);

        // حذف الإشعارات السابقة المتعلقة بهذا الطلب
        $this->employeeNotificationService->deleteExistingNotifications($request, 'permission_request_status_update');
        $this->employeeNotificationService->deleteExistingNotifications($request, 'permission_request_modified');
    }

    public function notifyManagerStatusUpdate(PermissionRequest $request): void
    {
        // حذف الإشعارات القديمة المتعلقة برد المدير
        $this->employeeNotificationService->deleteExistingNotifications($request, 'manager_response_update');

        $statusArabic = [
            'approved' => 'تمت الموافقة على',
            'rejected' => 'تم رفض',
            'pending' => 'في انتظار الرد على'
        ][$request->manager_status];

        $message = "{$statusArabic} طلب الاستئذان من قبل المدير";

        // إشعار للموظف
        $data = [
            'message' => $message,
            'status' => $request->manager_status,
            'rejection_reason' => $request->manager_rejection_reason,
            'is_manager_response' => true
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'manager_response_update', $data);

        // إشعار لل HR
        $this->managerNotificationService->notifyEmployee($request, 'manager_response_update', $message, true);
    }

    public function notifyHRStatusUpdate(PermissionRequest $request): void
    {
        // حذف الإشعارات القديمة المتعلقة برد HR
        $this->employeeNotificationService->deleteExistingNotifications($request, 'hr_response_update');

        $statusArabic = [
            'approved' => 'تمت الموافقة على',
            'rejected' => 'تم رفض',
            'pending' => 'في انتظار الرد على'
        ][$request->hr_status];

        $message = "{$statusArabic} طلب الاستئذان من قبل HR";

        // إشعار للموظف
        $data = [
            'message' => $message,
            'status' => $request->hr_status,
            'rejection_reason' => $request->hr_rejection_reason,
            'is_hr_response' => true
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'hr_response_update', $data);

        // إشعار للمدراء
        $this->managerNotificationService->notifyEmployee($request, 'hr_response_update', $message, false);
    }

    public function notifyStatusReset(PermissionRequest $request, string $type): void
    {
        // حذف كل الإشعارات السابقة المتعلقة بالرد
        if ($type === 'manager') {
            $this->employeeNotificationService->deleteExistingNotifications($request, 'manager_response_update');
            $this->employeeNotificationService->deleteExistingNotifications($request, 'manager_status_reset');
        } else {
            $this->employeeNotificationService->deleteExistingNotifications($request, 'hr_response_update');
            $this->employeeNotificationService->deleteExistingNotifications($request, 'hr_status_reset');
        }

        $roleType = $type === 'manager' ? 'المدير' : 'HR';
        $message = "تم إعادة تعيين رد {$roleType} على طلب الاستئذان";

        // إشعار للموظف فقط
        $data = [
            'message' => $message,
            'status' => 'pending',
            'is_reset' => true,
            'reset_by' => $type
        ];

        $this->employeeNotificationService->notifyEmployee($request, 'status_reset', $data);
    }
}
