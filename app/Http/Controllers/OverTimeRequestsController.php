<?php

namespace App\Http\Controllers;

use App\Models\OverTimeRequests;
use App\Services\OverTimeRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class OverTimeRequestsController extends Controller
{
    protected $overTimeRequestService;

    public function __construct(OverTimeRequestService $overTimeRequestService)
    {
        $this->overTimeRequestService = $overTimeRequestService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $employeeName = $request->input('employee_name');
        $status = $request->input('status');

        // تعريف متغير الفلاتر
        $filters = [
            'employeeName' => $employeeName,
            'status' => $status,
            'startDate' => $request->input('start_date'),
            'endDate' => $request->input('end_date')
        ];

        // التحقق من الصلاحيات
        $canCreateOvertime = $user->hasPermissionTo('create_overtime');
        $canUpdateOvertime = $user->hasPermissionTo('update_overtime');
        $canDeleteOvertime = $user->hasPermissionTo('delete_overtime');
        $canRespondAsManager = $user->hasPermissionTo('manager_respond_overtime_request');
        $canRespondAsHR = $user->hasPermissionTo('hr_respond_overtime_request');

        // حساب ساعات العمل الإضافي المعتمدة للمستخدم الحالي
        $myOvertimeHours = $this->overTimeRequestService->calculateOvertimeHours($user->id);

        // جلب طلبات المستخدم الحالي
        $myRequests = OverTimeRequests::with('user')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        // متغير لطلبات فريق HR
        $noTeamRequests = collect();
        $noTeamOvertimeHoursCount = [];

        // تجهيز طلبات الفريق للمدراء و HR
        if ($user->hasRole('hr')) {
            // جلب طلبات الموظفين الذين ليسوا في أي فريق
            $noTeamRequests = OverTimeRequests::query()
                ->with('user')
                ->whereHas('user', function ($query) {
                    $query->whereDoesntHave('teams');
                })
                ->latest();

            // حساب ساعات العمل الإضافي للموظفين بدون فريق
            $noTeamUserIds = $noTeamRequests->pluck('user_id')->unique();
            foreach ($noTeamUserIds as $userId) {
                $noTeamOvertimeHoursCount[$userId] = $this->overTimeRequestService->calculateOvertimeHours($userId);
            }

            // طلبات باقي الموظفين (الجدول الرئيسي)
            $teamRequests = OverTimeRequests::with('user')
                ->whereHas('user', function ($query) {
                    $query->whereHas('teams')
                        ->whereDoesntHave('teams', function ($q) {
                            $q->whereRaw('team_user.role = ?', ['admin']);
                        });
                })
                ->latest();
            $users = User::select('id', 'name')->get();
        } elseif ($user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $team = $user->currentTeam;
            if ($team) {
                $teamMembers = $team->users->pluck('id')->toArray();
                $teamRequests = OverTimeRequests::query()
                    ->with('user')
                    ->whereIn('user_id', $teamMembers)
                    ->whereHas('user', function ($query) use ($team) {
                        $query->whereDoesntHave('teams', function ($q) use ($team) {
                            $q->where('teams.id', $team->id)
                                ->whereRaw('team_user.role = ?', ['admin']);
                        });
                    })
                    ->latest();
                $users = User::whereIn('id', $teamMembers)->get();
            } else {
                $teamRequests = OverTimeRequests::query()->where('id', 0);
                $users = collect();
            }
        } else {
            $teamRequests = collect();
            $users = collect([$user]);
        }

        // حساب ساعات العمل الإضافي لكل مستخدم
        $overtimeHoursCount = [];
        if ($teamRequests instanceof \Illuminate\Database\Eloquent\Builder) {
            $userIds = $teamRequests->pluck('user_id')->unique();
            foreach ($userIds as $userId) {
                $overtimeHoursCount[$userId] = $this->overTimeRequestService->calculateOvertimeHours($userId);
            }
        }

        // تطبيق الفلاتر على طلبات الفريق
        if ($employeeName) {
            $teamRequests->whereHas('user', function ($q) use ($employeeName) {
                $q->where('name', 'like', "%{$employeeName}%");
            });

            if ($noTeamRequests instanceof \Illuminate\Database\Eloquent\Builder) {
                $noTeamRequests->whereHas('user', function ($q) use ($employeeName) {
                    $q->where('name', 'like', "%{$employeeName}%");
                });
            }
        }

        if ($status) {
            $teamRequests->where('status', $status);

            if ($noTeamRequests instanceof \Illuminate\Database\Eloquent\Builder) {
                $noTeamRequests->where('status', $status);
            }
        }

        // حساب عدد الطلبات المعلقة
        $pendingCount = 0;
        if ($teamRequests instanceof \Illuminate\Database\Eloquent\Builder) {
            $pendingCount = $teamRequests->clone()->where('status', 'pending')->count();
            $teamRequests = $teamRequests->paginate(10);
        }

        // تطبيق الترقيم الصفحي على طلبات الموظفين بدون فريق
        if ($noTeamRequests instanceof \Illuminate\Database\Eloquent\Builder) {
            $noTeamRequests = $noTeamRequests->paginate(10);
        }

        return view('overtime-requests.index', compact(
            'myRequests',
            'teamRequests',
            'noTeamRequests',
            'users',
            'canCreateOvertime',
            'canUpdateOvertime',
            'canDeleteOvertime',
            'canRespondAsManager',
            'canRespondAsHR',
            'myOvertimeHours',
            'overtimeHoursCount',
            'noTeamOvertimeHoursCount',
            'pendingCount',
            'filters'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'overtime_date' => 'required|date|after:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:255',
            'user_id' => 'sometimes|exists:users,id'
        ]);

        try {
            $this->overTimeRequestService->createRequest($request->all());
            return redirect()->route('overtime-requests.index')->with('success', 'Overtime request created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'overtime_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:255'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $this->overTimeRequestService->update($overtimeRequest, $request->all());
            return redirect()->route('overtime-requests.index')->with('success', 'Overtime request updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $this->overTimeRequestService->deleteRequest($overtimeRequest);
            return redirect()->route('overtime-requests.index')->with('success', 'Overtime request deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateManagerStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $this->overTimeRequestService->updateManagerStatus($overtimeRequest, $request->all());
            return back()->with('success', 'Response submitted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateHrStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $this->overTimeRequestService->updateHrStatus($overtimeRequest, $request->all());
            return back()->with('success', 'Response submitted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resetManagerStatus($id)
    {
        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $overtimeRequest->manager_status = 'pending';
            $overtimeRequest->manager_rejection_reason = null;
            $overtimeRequest->updateFinalStatus();
            $overtimeRequest->save();
            return back()->with('success', 'Status reset successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resetHrStatus($id)
    {
        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $overtimeRequest->hr_status = 'pending';
            $overtimeRequest->hr_rejection_reason = null;
            $overtimeRequest->updateFinalStatus();
            $overtimeRequest->save();
            return back()->with('success', 'Status reset successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function modifyManagerStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);

            // التحقق من الصلاحيات
            if (!Auth::user()->hasPermissionTo('manager_respond_overtime_request')) {
                return back()->with('error', 'Unauthorized action.');
            }

            // تحديث الحالة
            $overtimeRequest->manager_status = $request->status;
            $overtimeRequest->manager_rejection_reason = $request->status === 'rejected' ? $request->rejection_reason : null;
            $overtimeRequest->updateFinalStatus();
            $overtimeRequest->save();

            // إرسال إشعار
            $this->notificationService->notifyStatusUpdate($overtimeRequest);

            return back()->with('success', 'Manager response updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function modifyHrStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);

            // التحقق من الصلاحيات
            if (!Auth::user()->hasPermissionTo('hr_respond_overtime_request')) {
                return back()->with('error', 'Unauthorized action.');
            }

            // تحديث الحالة
            $overtimeRequest->hr_status = $request->status;
            $overtimeRequest->hr_rejection_reason = $request->status === 'rejected' ? $request->rejection_reason : null;
            $overtimeRequest->updateFinalStatus();
            $overtimeRequest->save();

            // إرسال إشعار
            $this->notificationService->notifyStatusUpdate($overtimeRequest);

            return back()->with('success', 'HR response updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
