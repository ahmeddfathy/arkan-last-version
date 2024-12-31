<?php

namespace App\Services;

use App\Models\AbsenceRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class AbsenceRequestService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function getAllRequests()
    {
        $user = Auth::user();

        if ($user->role === 'manager') {
            return AbsenceRequest::with('user')
                ->latest()
                ->paginate(10);
        }

        return AbsenceRequest::where('user_id', $user->id)
            ->latest()
            ->paginate(10);
    }

    public function getUserRequests()
    {
        $user = Auth::user();

        if ($user->role === 'manager') {
            return AbsenceRequest::with('user')
                ->latest()
                ->paginate(10);
        }

        return AbsenceRequest::where('user_id', $user->id)
            ->latest()
            ->paginate(10);
    }

    public function createRequest(array $data)
    {
        $userId = Auth::id();
        $existingRequest = AbsenceRequest::where('user_id', $userId)
            ->where('absence_date', $data['absence_date'])
            ->first();

        if ($existingRequest) {
            return redirect()->back()->withErrors(['absence_date' => 'You have already requested this day off.']);
        }

        $request = AbsenceRequest::create([
            'user_id' => $userId,
            'absence_date' => $data['absence_date'],
            'reason' => $data['reason'],
            'status' => 'pending'
        ]);

        // Send notification to managers
        $this->notificationService->createLeaveRequestNotification($request);

        return $request;
    }

    public function createRequestForUser(int $userId, array $data)
    {
        $request = AbsenceRequest::create([
            'user_id' => $userId,
            'absence_date' => $data['absence_date'],
            'reason' => $data['reason'],
            'status' => 'pending'
        ]);

        // Send notification to managers
        $this->notificationService->createLeaveRequestNotification($request);

        return $request;
    }

    public function updateRequest(AbsenceRequest $request, array $data)
    {
        $existingRequest = AbsenceRequest::where('user_id', $request->user_id)
            ->where('absence_date', $data['absence_date'])
            ->where('id', '!=', $request->id)
            ->first();

        if ($existingRequest) {
            return redirect()->back()->withErrors(['absence_date' => 'You have already requested this day off.']);
        }

        $request->update([
            'absence_date' => $data['absence_date'],
            'reason' => $data['reason']
        ]);

        // Notify managers about the modification
        $this->notificationService->notifyRequestModified($request);

        return $request;
    }

    public function deleteRequest(AbsenceRequest $request)
    {
        // Notify managers about the deletion before deleting the request
        $this->notificationService->notifyRequestDeleted($request);
        return $request->delete();
    }

    public function updateStatus(AbsenceRequest $request, array $data)
    {
        if (!$this->canRespond()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You are not authorized to respond to absence requests.');
        }

        $request->update([
            'status' => $data['status'],
            'rejection_reason' => $data['status'] == 'rejected' ? $data['rejection_reason'] : null
        ]);

        // Send notification to employee about the status update
        $this->notificationService->createStatusUpdateNotification($request);

        return $request;
    }

    public function resetStatus(AbsenceRequest $request)
    {
        $request->update([
            'status' => 'pending',
            'rejection_reason' => null
        ]);

        // Delete existing status notifications
        $this->notificationService->deleteStatusNotifications($request);

        return $request;
    }

    public function modifyResponse(AbsenceRequest $request, array $data)
    {
        // Delete existing notifications before updating status
        $this->notificationService->deleteStatusNotifications($request);

        if ($data['response_type'] === 'manager') {
            $request->manager_status = $data['status'];
            // مسح سبب الرفض إذا تم تغيير الحالة إلى موافق
            $request->manager_rejection_reason = $data['status'] === 'rejected' ? $data['rejection_reason'] : null;
        } elseif ($data['response_type'] === 'hr') {
            $request->hr_status = $data['status'];
            // مسح سبب الرفض إذا تم تغيير الحالة إلى موافق
            $request->hr_rejection_reason = $data['status'] === 'rejected' ? $data['rejection_reason'] : null;
        }

        // تحديث الحالة النهائية
        $request->updateFinalStatus();
        $request->save();

        // Create new notification with updated status
        $this->notificationService->createStatusUpdateNotification($request);

        return $request;
    }
    public function calculateAbsenceDays($userId)
    {
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        $count = AbsenceRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [$startOfYear, $endOfYear])
            ->count();

        return $count;
    }

    public function getFilteredRequests($employeeName = null, $status = null)
    {
        $query = AbsenceRequest::with('user')->latest();

        if ($employeeName) {
            $query->whereHas('user', function ($q) use ($employeeName) {
                $q->where('name', 'like', "%{$employeeName}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate(10);
    }

    public function canRespond($user = null)
    {
        $user = $user ?? Auth::user();

        // التحقق من صلاحيات المديرين
        if (
            $user->hasRole(['team_leader', 'department_manager', 'company_manager']) &&
            $user->hasPermissionTo('manager_respond_absence_request')
        ) {
            return true;
        }

        // التحقق من صلاحيات HR
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
            return true;
        }

        return false;
    }

    public function canModifyRequest(AbsenceRequest $request, $user = null)
    {
        $user = $user ?? Auth::user();

        // يمكن للمستخدم تعديل طلبه الخاص إذا كان معلقاً
        if ($user->id === $request->user_id && $request->status === 'pending') {
            return true;
        }

        // يمكن للمديرين تعديل الطلبات إذا كان لديهم الصلاحية
        if (
            $user->hasRole(['team_leader', 'department_manager', 'company_manager']) &&
            $user->hasPermissionTo('manager_respond_absence_request')
        ) {
            return true;
        }

        // يمكن لـ HR تعديل الطلبات إذا كان لديه الصلاحية
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
            return true;
        }

        return false;
    }
}
