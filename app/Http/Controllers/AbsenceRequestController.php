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
        $user = Auth::user();
        $employeeName = $request->input('employee_name');
        $status = $request->input('status');

        // التحقق من الصلاحيات
        $canCreateAbsence = $user->hasPermissionTo('create_absence');
        $canUpdateAbsence = $user->hasPermissionTo('update_absence');
        $canDeleteAbsence = $user->hasPermissionTo('delete_absence');
        $canRespondAsManager = $user->hasPermissionTo('manager_respond_absence_request');
        $canRespondAsHR = $user->hasPermissionTo('hr_respond_absence_request');

        // تجهيز الطلبات حسب الدور
        if ($user->hasRole('hr')) {
            $requests = AbsenceRequest::with('user')->latest();
            $users = User::select('id', 'name')->get();
        } elseif ($user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            // نستخدم علاقات Jetstream للوصول للفريق
            $team = $user->currentTeam;

            if ($team) {
                \Log::info('Current Team:', ['team_id' => $team->id, 'team_name' => $team->name]);

                // نجلب أعضاء الفريق
                $teamMembers = $team->users->pluck('id')->toArray();

                \Log::info('Team Members:', ['members' => $teamMembers]);

                // نجلب طلبات الغياب
                $requests = AbsenceRequest::query()
                    ->with('user')
                    ->whereIn('user_id', $teamMembers)
                    ->latest();

                $users = User::whereIn('id', $teamMembers)->get();

                \Log::info('Requests Query:', [
                    'sql' => $requests->toSql(),
                    'bindings' => $requests->getBindings(),
                    'count' => $requests->count()
                ]);
            } else {
                // إذا لم يكن لديه فريق حالي
                $requests = AbsenceRequest::query()->where('id', 0); // فريق فارغ
                $users = collect();
                \Log::warning('No current team found for user:', ['user_id' => $user->id]);
            }
        } else {
            $requests = AbsenceRequest::with('user')
                ->where('user_id', $user->id)
                ->latest();
            $users = collect([$user]);
        }

        // تطبيق الفلاتر
        if ($employeeName) {
            $requests->whereHas('user', function ($q) use ($employeeName) {
                $q->where('name', 'like', "%{$employeeName}%");
            });
        }

        if ($status) {
            $requests->where('status', $status);
        }

        $requests = $requests->paginate(10);

        // حساب عدد أيام الغياب المعتمدة لكل مستخدم
        $absenceDays = collect();
        foreach ($requests as $absenceRequest) {
            if (!$absenceDays->has($absenceRequest->user_id)) {
                $count = $this->absenceRequestService->calculateAbsenceDays($absenceRequest->user_id);
                $absenceDays->put($absenceRequest->user_id, $count);
            }
        }

        return view('absence-requests.index', compact(
            'requests',
            'users',
            'canCreateAbsence',
            'canUpdateAbsence',
            'canDeleteAbsence',
            'canRespondAsManager',
            'canRespondAsHR',
            'absenceDays'
        ));
    }

    public function store(Request $request)
    {
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
