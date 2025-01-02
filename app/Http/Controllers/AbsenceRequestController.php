<?php

namespace App\Http\Controllers;

use App\Models\AbsenceRequest;
use App\Services\AbsenceRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Team;

class AbsenceRequestController extends Controller
{
    protected $absenceRequestService;

    public function __construct(AbsenceRequestService $absenceRequestService)
    {
        $this->absenceRequestService = $absenceRequestService;
    }

    public function index(Request $request)
    {
        if (!auth()->user()->hasAnyPermission(['view_absence', 'create_absence', 'update_absence', 'delete_absence'])) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $employeeName = $request->input('employee_name');
        $status = $request->input('status');

        // التحقق من الصلاحيات
        $canCreateAbsence = $user->hasPermissionTo('create_absence');
        $canUpdateAbsence = $user->hasPermissionTo('update_absence');
        $canDeleteAbsence = $user->hasPermissionTo('delete_absence');
        $canRespondAsManager = $user->hasPermissionTo('manager_respond_absence_request');
        $canRespondAsHR = $user->hasPermissionTo('hr_respond_absence_request');

        // جساب عدد أيام الغياب المعتمدة للمستخدم الحالي
        $myAbsenceDays = AbsenceRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereYear('absence_date', now()->year)
            ->count();

        // جلب طلبات المستخدم الحالي
        $myRequests = AbsenceRequest::with('user')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        // متغير لطلبات فريق HR
        $noTeamRequests = collect();
        $noTeamAbsenceDaysCount = [];

        // تجهيز طلبات الفريق للمدراء و HR
        if ($user->hasRole('hr')) {
            // جلب طلبات الموظفين الذين ليسوا في أي فريق
            $noTeamRequests = AbsenceRequest::query()
                ->with('user')
                ->whereHas('user', function ($query) {
                    $query->whereDoesntHave('teams');
                })
                ->latest();

            // طساب أيام الغياب للموظفين بدون فريق
            $noTeamUserIds = $noTeamRequests->pluck('user_id')->unique();
            $noTeamAbsenceDaysCount = [];
            foreach ($noTeamUserIds as $userId) {
                $noTeamAbsenceDaysCount[$userId] = AbsenceRequest::where('user_id', $userId)
                    ->where('status', 'approved')
                    ->whereYear('absence_date', now()->year)
                    ->count();
            }

            // طلبات باقي الموظفين (الجدول الرئيسي)
            $teamRequests = AbsenceRequest::with('user')
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
                $teamRequests = AbsenceRequest::query()
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
                $teamRequests = AbsenceRequest::query()->where('id', 0);
                $users = collect();
            }
        } else {
            $teamRequests = collect();
            $users = collect([$user]);
        }

        // حساب عدد أيام الغياب لكل مستخدم
        $absenceDaysCount = [];
        if ($teamRequests instanceof \Illuminate\Database\Eloquent\Builder) {
            $userIds = $teamRequests->pluck('user_id')->unique();
            foreach ($userIds as $userId) {
                $absenceDaysCount[$userId] = AbsenceRequest::where('user_id', $userId)
                    ->where('status', 'approved')
                    ->whereYear('absence_date', now()->year)
                    ->count();
            }
        }

        // تطبيق الفلاتر على طلبات الفريق
        if ($employeeName) {
            $teamRequests->whereHas('user', function ($q) use ($employeeName) {
                $q->where('name', 'like', "%{$employeeName}%");
            });

            // تطبيق الفلتر على طلبات الموظفين بدون فريق
            if ($noTeamRequests instanceof \Illuminate\Database\Eloquent\Builder) {
                $noTeamRequests->whereHas('user', function ($q) use ($employeeName) {
                    $q->where('name', 'like', "%{$employeeName}%");
                });
            }
        }

        if ($status) {
            $teamRequests->where('status', $status);

            // تطبيق فلتر الحالة على طلبات الموظفين بدون فريق
            if ($noTeamRequests instanceof \Illuminate\Database\Eloquent\Builder) {
                $noTeamRequests->where('status', $status);
            }
        }

        if ($teamRequests instanceof \Illuminate\Database\Eloquent\Builder) {
            $teamRequests = $teamRequests->paginate(10);
        }

        // تطبيق الترقيم الصفحي على طلبات الموظفين بدون فريق بعد تطبيق الفلاتر
        if ($noTeamRequests instanceof \Illuminate\Database\Eloquent\Builder) {
            $noTeamRequests = $noTeamRequests->paginate(10);
        }

        return view('absence-requests.index', compact(
            'myRequests',
            'teamRequests',
            'noTeamRequests',
            'users',
            'canCreateAbsence',
            'canUpdateAbsence',
            'canDeleteAbsence',
            'canRespondAsManager',
            'canRespondAsHR',
            'myAbsenceDays',
            'absenceDaysCount',
            'noTeamAbsenceDaysCount'
        ));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('create_absence')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();

        if ($user->role !== 'employee' && $user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        // تحديد المستخدم المستهدف بناءً على دور المدير أو الموظف
        $targetUserId = $user->role === 'manager' && $request->input('user_id')
            ? $request->input('user_id')
            : $user->id;

        // حساب عدد الأيام الحالية (pending أو approved) للسنة الحالية
        $pendingOrApprovedDays = AbsenceRequest::where('user_id', $targetUserId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereYear('absence_date', Carbon::now()->year) // فقط للسنة الحالية
            ->count();

        if ($pendingOrApprovedDays >= 5) {
            return redirect()->back()->with('error', 'You cannot request more than 5 absence days in a year while your pending or approved requests exist.');
        }

        // تحقق من صحة البيانات المدخلة
        $validated = $request->validate([
            'absence_date' => 'required|date|after:today',
            'reason' => 'required|string|max:255',
            'user_id' => 'required_if:role,manager|exists:users,id|nullable',
        ]);

        // التحقق من أن الطلب الجديد لا يتجاوز الحد
        if ($pendingOrApprovedDays + 1 > 5) {
            return redirect()->back()->with('error', 'This request exceeds the allowed limit of 5 days per year.');
        }

        // إنشاء الطلب بناءً على دور المستخدم
        if ($user->role === 'manager') {
            if ($request->input('user_id') && $request->input('user_id') !== $user->id) {
                $this->absenceRequestService->createRequestForUser($validated['user_id'], $validated);
            } else {
                $this->absenceRequestService->createRequest($validated);
            }
        } else {
            $this->absenceRequestService->createRequest($validated);
        }

        return redirect()->route('absence-requests.index')
            ->with('success', 'Absence request submitted successfully.');
    }

    public function update(Request $request, AbsenceRequest $absenceRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $absenceRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'absence_date' => 'required|date|after:today',
            'reason' => 'required|string|max:255'
        ]);

        $this->absenceRequestService->updateRequest($absenceRequest, $validated);

        return redirect()->route('absence-requests.index')
            ->with('success', 'Absence request updated successfully.');
    }

    public function destroy(AbsenceRequest $absenceRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $absenceRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $this->absenceRequestService->deleteRequest($absenceRequest);

        return redirect()->route('absence-requests.index')
            ->with('success', 'Absence request deleted successfully.');
    }

    public function updateStatus(Request $request, AbsenceRequest $absenceRequest)
    {
        $user = Auth::user();

        // التحقق من الصلاحيات أولاً
        if ($user->hasRole('team_leader') && !$user->hasPermissionTo('manager_respond_absence_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الغياب');
        }

        if ($user->hasRole('hr') && !$user->hasPermissionTo('hr_respond_absence_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الغياب');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
            'response_type' => 'required|in:manager,hr'
        ]);

        // التحقق من نوع الرد وتحديث الحالة بناءً على الدور
        if ($validated['response_type'] === 'manager' && $user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $absenceRequest->manager_status = $validated['status'];
            $absenceRequest->manager_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        } elseif ($validated['response_type'] === 'hr' && $user->hasRole('hr')) {
            $absenceRequest->hr_status = $validated['status'];
            $absenceRequest->hr_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        } else {
            return redirect()->back()->with('error', 'نوع الرد غير صحيح');
        }

        // تحديث الحالة النهائية
        $absenceRequest->updateFinalStatus();
        $absenceRequest->save();

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    public function modifyResponse(Request $request, $id)
    {
        $user = Auth::user();
        $absenceRequest = AbsenceRequest::findOrFail($id);

        // التحقق من الصلاحيات
        if (!($user->hasRole(['team_leader', 'department_manager', 'company_manager']) || $user->hasRole('hr'))) {
            return redirect()->back()->with('error', 'غير مصرح لك بتعديل الرد');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
            'response_type' => 'required|in:manager,hr'
        ]);

        // التأكد من أن المدير يعدل رده فقط وHR يعدل رده فقط
        if ($validated['response_type'] === 'manager' && $user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $absenceRequest->manager_status = $validated['status'];
            // مسح سبب الرفض إذا تم تغيير الحالة إلى موافق
            $absenceRequest->manager_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        } elseif ($validated['response_type'] === 'hr' && $user->hasRole('hr')) {
            $absenceRequest->hr_status = $validated['status'];
            // مسح سبب الرفض إذا تم تغيير الحالة إلى موافق
            $absenceRequest->hr_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        } else {
            return redirect()->back()->with('error', 'نوع الرد غير صحيح');
        }

        // تحديث الحالة النهائية
        $absenceRequest->updateFinalStatus();
        $absenceRequest->save();

        return redirect()->back()->with('success', 'تم تعديل الرد بنجاح');
    }

    public function resetStatus(AbsenceRequest $absenceRequest)
    {
        $user = Auth::user();
        $responseType = request('response_type');

        if ($responseType === 'manager' && $user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $absenceRequest->manager_status = 'pending';
            $absenceRequest->manager_rejection_reason = null;
        } elseif ($responseType === 'hr' && $user->hasRole('hr')) {
            $absenceRequest->hr_status = 'pending';
            $absenceRequest->hr_rejection_reason = null;
        } else {
            return redirect()->back()->with('error', 'غير مصرح لك بإعادة تعيين الحالة');
        }

        // تحديث الحالة النهائية
        $absenceRequest->updateFinalStatus();
        $absenceRequest->save();

        return redirect()->back()->with('success', 'تم إعادة تعيين الحالة بنجاح');
    }
}
