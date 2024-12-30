<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AbsenceRequest;
use App\Models\PermissionRequest;
use App\Models\OverTimeRequests;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeStatisticsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = User::where('role', 'employee');

        if ($user->role === 'manager') {
            if ($request->has('department') && $request->department != '') {
                $query->where('department', $request->department);
            }
        } elseif ($user->role === 'leader') {
            $query->where('department', $user->department);
        }

        if ($request->has('search') && $request->search != '') {
            $query->where('employee_id', $request->search);
        }

        // جلب قائمة الأقسام للفلتر (للمدير فقط)
        $departments = [];
        if ($user->role === 'manager') {
            $departments = User::where('role', 'employee')
                ->select('department')
                ->distinct()
                ->whereNotNull('department')
                ->pluck('department');
        }

        // ترتيب الموظفين حسب القسم ثم الاسم
        $query->orderBy('department')
            ->orderBy('name');

        // تعيين التواريخ الافتراضية
        $startDate = $request->start_date ?? Carbon::now()->startOfWeek(Carbon::SATURDAY)->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::now()->endOfWeek(Carbon::THURSDAY)->format('Y-m-d');

        // جلب المستخدمين مع الترقيم
        $employees = $query->paginate(10)->withQueryString();

        // جلب كل المستخدمين للفلتر
        $allUsers = User::select('id', 'name', 'employee_id')
            ->where('role', 'employee')
            ->when($user->role === 'leader', function ($q) use ($user) {
                $q->where('department', $user->department);
            })
            ->get();

        // حساب الإحصائيات لكل موظف
        foreach ($employees as $employee) {
            if ($startDate && $endDate) {
                $statsQuery = AttendanceRecord::where('employee_id', $employee->employee_id)
                    ->whereBetween('attendance_date', [$startDate, $endDate]);

                // حساب أجمالي أيام العمل (فقط أيام الحضور والغياب)
                $totalWorkDays = (clone $statsQuery)
                    ->where(function ($query) {
                        $query->where('status', 'حضـور')
                            ->orWhere('status', 'غيــاب');
                    })
                    ->count();
                $employee->total_working_days = $totalWorkDays;

                // حساب أيام الحضور
                $employee->actual_attendance_days = (clone $statsQuery)
                    ->where('status', 'حضـور')
                    ->whereNotNull('entry_time')
                    ->count();

                // حساب أيام الغياب
                $employee->absences = (clone $statsQuery)
                    ->where('status', 'غيــاب')
                    ->count();

                // حساب أيام العطل الأسبوعية
                $employee->weekend_days = (clone $statsQuery)
                    ->where('status', 'عطله إسبوعية')
                    ->count();

                // حساب التأخير
                $lateRecords = (clone $statsQuery)
                    ->where('delay_minutes', '>', 0)
                    ->whereNotNull('entry_time')
                    ->get();

                $employee->delays = $lateRecords->sum('delay_minutes');

                // حساب متوسط ساعات العمل
                $workingHoursRecords = (clone $statsQuery)
                    ->where('status', 'حضـور')
                    ->whereNotNull('working_hours')
                    ->get();

                $totalWorkingHours = $workingHoursRecords->sum('working_hours');
                $daysWithHours = $workingHoursRecords->count();
                $employee->average_working_hours = $daysWithHours > 0 ? round($totalWorkingHours / $daysWithHours, 2) : 0;

                // نسبة الحضور
                $employee->attendance_percentage = $totalWorkDays > 0
                    ? round(($employee->actual_attendance_days / $totalWorkDays) * 100, 1)
                    : 0;
            } else {
                $employee->total_working_days = 0;
                $employee->actual_attendance_days = 0;
                $employee->absences = 0;
                $employee->weekend_days = 0;
                $employee->delays = 0;
                $employee->average_working_hours = 0;
                $employee->attendance_percentage = 0;
            }

            // الأذونات
            $permissionQuery = PermissionRequest::where('user_id', $employee->id)
                ->where('status', 'approved');
            if ($startDate && $endDate) {
                $permissionQuery->whereBetween('departure_time', [$startDate, $endDate]);
            }
            $employee->permissions = $permissionQuery->count();

            // الوقت الإضافي
            $overtimeQuery = OverTimeRequests::where('user_id', $employee->id)
                ->where('status', 'approved');
            if ($startDate && $endDate) {
                $overtimeQuery->whereBetween('overtime_date', [$startDate, $endDate]);
            }
            $employee->overtimes = $overtimeQuery->count();
        }

        return view('employee-statistics.index', compact(
            'employees',
            'startDate',
            'endDate',
            'allUsers',
            'departments'
        ));
    }

    public function getEmployeeDetails($employee_id)
    {
        $employee = User::where('employee_id', $employee_id)->firstOrFail();
        $startDate = request('start_date');
        $endDate = request('end_date');

        $statsQuery = AttendanceRecord::where('employee_id', $employee_id)
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        $statistics = [
            'total_working_days' => (clone $statsQuery)
                ->where(function ($query) {
                    $query->where('status', 'حضـور')
                        ->orWhere('status', 'غيــاب');
                })
                ->count(),

            'actual_attendance_days' => (clone $statsQuery)
                ->where('status', 'حضـور')
                ->whereNotNull('entry_time')
                ->count(),

            'absences' => (clone $statsQuery)
                ->where('status', 'غيــاب')
                ->count(),

            'permissions' => PermissionRequest::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('departure_time', [$startDate, $endDate])
                ->count(),

            'overtimes' => OverTimeRequests::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('overtime_date', [$startDate, $endDate])
                ->count(),

            'delays' => (clone $statsQuery)
                ->where('delay_minutes', '>', 0)
                ->whereNotNull('entry_time')
                ->sum('delay_minutes'),

            'attendance' => $statsQuery->orderBy('attendance_date', 'desc')->get()
        ];

        $statistics['attendance_percentage'] = $statistics['total_working_days'] > 0
            ? round(($statistics['actual_attendance_days'] / $statistics['total_working_days']) * 100, 1)
            : 0;

        return response()->json([
            'employee' => $employee,
            'statistics' => $statistics
        ]);
    }
}
