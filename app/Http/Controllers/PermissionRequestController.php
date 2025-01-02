<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PermissionRequest;
use App\Services\PermissionRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\PermissionRequestStatusUpdated;

class PermissionRequestController extends Controller
{
    protected $permissionRequestService;

    public function __construct(PermissionRequestService $permissionRequestService)
    {
        $this->permissionRequestService = $permissionRequestService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $employeeName = $request->input('employee_name');
        $status = $request->input('status');

        // جلب طلبات المستخدم الحالي
        $myRequests = PermissionRequest::with('user')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        // حساب الدقائق المتبقية للمستخدم الحالي
        $myRemainingMinutes = $this->permissionRequestService->getRemainingMinutes($user->id);

        // جلب طلبات الفريق للمدراء و HR
        $teamRequests = PermissionRequest::where('id', 0)->paginate(10); // قيمة افتراضية فارغة
        $noTeamRequests = PermissionRequest::where('id', 0)->paginate(10); // قيمة افتراضية فارغة
        $remainingMinutes = [];
        $noTeamRemainingMinutes = [];

        if ($user->hasRole('hr')) {
            // جلب طلبات الموظفين الذين لديهم فريق
            $teamQuery = PermissionRequest::with(['user', 'violations'])
                ->whereHas('user', function ($q) {
                    $q->whereHas('teams');
                });

            if ($employeeName) {
                $teamQuery->whereHas('user', function ($q) use ($employeeName) {
                    $q->where('name', 'like', "%{$employeeName}%");
                });
            }

            if ($status) {
                $teamQuery->where('status', $status);
            }

            $teamRequests = $teamQuery->latest()->paginate(10);

            // جلب طلبات الموظفين الذين ليس لديهم فريق في جدول منفصل
            $noTeamQuery = PermissionRequest::with(['user', 'violations'])
                ->whereHas('user', function ($q) {
                    $q->whereDoesntHave('teams');
                });

            if ($employeeName) {
                $noTeamQuery->whereHas('user', function ($q) use ($employeeName) {
                    $q->where('name', 'like', "%{$employeeName}%");
                });
            }

            if ($status) {
                $noTeamQuery->where('status', $status);
            }

            $noTeamRequests = $noTeamQuery->latest()->paginate(10);

            // حساب الدقائق المتبقية للموظفين بدون فريق
            $noTeamUserIds = $noTeamRequests->pluck('user_id')->unique();
            foreach ($noTeamUserIds as $userId) {
                $noTeamRemainingMinutes[$userId] = $this->permissionRequestService->getRemainingMinutes($userId);
            }

            // حساب الدقائق المتبقية للموظفين في الفرق
            $teamUserIds = $teamRequests->pluck('user_id')->unique();
            foreach ($teamUserIds as $userId) {
                $remainingMinutes[$userId] = $this->permissionRequestService->getRemainingMinutes($userId);
            }
        } elseif ($user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            // جلب طلبات الفريق للمدراء
            $team = $user->currentTeam;
            if ($team) {
                $teamMembers = $team->users->pluck('id')->toArray();
                $query = PermissionRequest::with(['user', 'violations'])
                    ->whereIn('user_id', $teamMembers);

                if ($employeeName) {
                    $query->whereHas('user', function ($q) use ($employeeName) {
                        $q->where('name', 'like', "%{$employeeName}%");
                    });
                }

                if ($status) {
                    $query->where('status', $status);
                }

                $teamRequests = $query->latest()->paginate(10);

                // حساب الدقائق المتبقية لأعضاء الفريق
                foreach ($teamMembers as $userId) {
                    $remainingMinutes[$userId] = $this->permissionRequestService->getRemainingMinutes($userId);
                }
            }
        }

        // جلب قائمة المستخدمين للبحث
        $users = User::when($user->hasRole('hr'), function ($query) {
            // HR يرى فقط المستخدمين الذين ليس لديهم فريق
            return $query->whereDoesntHave('teams');
        }, function ($query) use ($user) {
            if ($user->currentTeam) {
                return $query->whereIn('id', $user->currentTeam->users->pluck('id'));
            }
            return $query->where('id', $user->id);
        })->get();

        return view('permission-requests.index', compact(
            'myRequests',
            'teamRequests',
            'noTeamRequests',
            'users',
            'myRemainingMinutes',
            'remainingMinutes',
            'noTeamRemainingMinutes'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'employee' && $user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'departure_time' => 'required|date|after:now',
            'return_time' => 'required|date|after:departure_time',
            'reason' => 'required|string|max:255',
            'user_id' => 'required_if:role,manager|exists:users,id|nullable'
        ]);

        if ($user->role === 'manager' && $request->input('user_id') && $request->input('user_id') !== $user->id) {
            $result = $this->permissionRequestService->createRequestForUser($validated['user_id'], $validated);
        } else {
            $result = $this->permissionRequestService->createRequest($validated);
        }

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('permission-requests.index')
            ->with('success', 'Permission request submitted successfully.');
    }

    public function resetStatus(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $this->permissionRequestService->resetStatus($permissionRequest);

        return redirect()->route('permission-requests.index')
            ->with('success', 'Request status reset to pending successfully.');
    }

    public function modifyResponse(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        $this->permissionRequestService->modifyResponse($permissionRequest, $validated);

        return redirect()->route('permission-requests.index')
            ->with('success', 'Response modified successfully.');
    }

    public function update(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $permissionRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'departure_time' => 'required|date|after:now',
            'return_time' => 'required|date|after:departure_time',

            'reason' => 'required|string|max:255',
            'returned_on_time' => 'nullable|boolean',
            'minutes_used' => 'nullable|integer'
        ]);

        $result = $this->permissionRequestService->updateRequest($permissionRequest, $validated);

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('permission-requests.index')
            ->with('success', 'Permission request updated successfully.');
    }

    public function destroy(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $permissionRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $this->permissionRequestService->deleteRequest($permissionRequest);

        return redirect()->route('permission-requests.index')
            ->with('success', 'Permission request deleted successfully.');
    }

    public function updateStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if ($user->hasRole('team_leader') && !$user->hasPermissionTo('manager_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الاستئذان');
        }

        if ($user->hasRole('hr') && !$user->hasPermissionTo('hr_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الاستئذان');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
            'response_type' => 'required|in:manager,hr'
        ]);

        // التحقق من نوع الرد وتحديث الحالة
        if ($validated['response_type'] === 'manager' && $user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $permissionRequest->manager_status = $validated['status'];
            $permissionRequest->manager_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        } elseif ($validated['response_type'] === 'hr' && $user->hasRole('hr')) {
            $permissionRequest->hr_status = $validated['status'];
            $permissionRequest->hr_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        } else {
            return redirect()->back()->with('error', 'نوع الرد غير صحيح');
        }

        // تحديث الحالة النهائية
        $permissionRequest->updateFinalStatus();
        $permissionRequest->save();

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    public function updateReturnStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'return_status' => 'required|in:0,1,2',
        ]);

        $this->permissionRequestService->updateReturnStatus($permissionRequest, (int)$validated['return_status']);

        return redirect()->route('permission-requests.index')
            ->with('success', 'Return status updated successfully.');
    }

    public function updateHrStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $permissionRequest = PermissionRequest::findOrFail($id);
            $user = Auth::user();

            // التحقق من الصلاحيات
            if (!$user->hasRole('hr') || !$user->hasPermissionTo('hr_respond_permission_request')) {
                return back()->with('error', 'Unauthorized action.');
            }

            // تحديث حالة الطلب
            $permissionRequest->updateHrStatus(
                $request->status,
                $request->status === 'rejected' ? $request->rejection_reason : null
            );

            return back()->with('success', 'Response submitted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error in updateHrStatus: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while updating the status.');
        }
    }

    public function modifyHrStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // التحقق من صلاحيات HR
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('hr_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية تعديل الرد على طلبات الاستئذان');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        $permissionRequest->hr_status = $validated['status'];
        $permissionRequest->hr_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;

        // تحديث الحالة النهائية
        $permissionRequest->updateFinalStatus();
        $permissionRequest->save();

        return redirect()->back()->with('success', 'تم تعديل الرد بنجاح');
    }

    public function resetHrStatus(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // التحقق من صلاحيات HR
        if (!$user->hasRole('hr') || !$user->hasPermissionTo('hr_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية إعادة تعيين الرد على طلبات الاستئذان');
        }

        $permissionRequest->hr_status = 'pending';
        $permissionRequest->hr_rejection_reason = null;

        // تحديث الحالة النهائية
        $permissionRequest->updateFinalStatus();
        $permissionRequest->save();

        return redirect()->back()->with('success', 'تم إعادة تعيين الرد بنجاح');
    }

    public function updateManagerStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $permissionRequest = PermissionRequest::findOrFail($id);
            $user = Auth::user();

            // التحقق من الصلاحيات
            if (
                !$user->hasRole(['team_leader', 'department_manager', 'company_manager']) ||
                !$user->hasPermissionTo('manager_respond_permission_request')
            ) {
                return back()->with('error', 'Unauthorized action.');
            }

            // تحديث حالة الطلب
            $permissionRequest->updateManagerStatus(
                $request->status,
                $request->status === 'rejected' ? $request->rejection_reason : null
            );

            return back()->with('success', 'Response submitted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error in updateManagerStatus: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while updating the status.');
        }
    }

    public function resetManagerStatus(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (
            !$user->hasRole(['team_leader', 'department_manager', 'company_manager']) ||
            !$user->hasPermissionTo('manager_respond_permission_request')
        ) {
            return back()->with('error', 'Unauthorized action.');
        }

        try {
            // إعادة تعيين حالة المدير
            $permissionRequest->manager_status = 'pending';
            $permissionRequest->manager_rejection_reason = null;

            // تحديث الحالة النهائية
            $permissionRequest->updateFinalStatus();
            $permissionRequest->save();

            return back()->with('success', 'Manager response has been reset successfully.');
        } catch (\Exception $e) {
            \Log::error('Error in resetManagerStatus: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while resetting the status.');
        }
    }

    public function modifyManagerStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (
            !$user->hasRole(['team_leader', 'department_manager', 'company_manager']) ||
            !$user->hasPermissionTo('manager_respond_permission_request')
        ) {
            return back()->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        try {
            // تحديث حالة المدير
            $permissionRequest->manager_status = $validated['status'];
            $permissionRequest->manager_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;

            // تحديث الحالة النهائية
            $permissionRequest->updateFinalStatus();
            $permissionRequest->save();

            return back()->with('success', 'Manager response has been modified successfully.');
        } catch (\Exception $e) {
            \Log::error('Error in modifyManagerStatus: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while modifying the status.');
        }
    }
}
