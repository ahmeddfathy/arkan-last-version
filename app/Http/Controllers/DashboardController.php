<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\SalarySheet;
use App\Models\AbsenceRequest;
use App\Models\PermissionRequest;
use App\Models\OverTimeRequests;
use App\Models\Violation;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Services\AttendanceReportService;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // تهيئة المتغير بقيم افتراضية
        $attendanceStats = [
            'present_days' => 0,
            'absent_days' => 0,
            'violation_days' => 0,
            'late_days' => 0,
            'total_delay_minutes' => 0,
            'avg_delay_minutes' => 0,
            'max_delay_minutes' => 0
        ];

        // تحديد بداية ونهاية الشهر (من 26 للشهر السابق إلى 25 للشهر الحالي)
        $now = now();
        $startDate = $now->copy()->subMonth()->setDay(26)->startOfDay();
        $endDate = $now->copy()->setDay(25)->endOfDay();

        // Add period information
        $attendanceStats['period'] = [
            'month' => $now->translatedFormat('F'),
            'year' => $now->year
        ];

        // Get salary files for the user
        $salaryFiles = SalarySheet::where('employee_id', $user->employee_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $statsQuery = AttendanceRecord::where('employee_id', $user->employee_id)
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        // حساب أجمالي أيام العمل (فقط أيام الحضور والغياب)
        $totalWorkDays = (clone $statsQuery)
            ->where(function ($query) {
                $query->where('status', 'حضـور')
                    ->orWhere('status', 'غيــاب');
            })
            ->count();

        // حساب أيام الحضور
        $attendanceStats['present_days'] = (clone $statsQuery)
            ->where('status', 'حضـور')
            ->whereNotNull('entry_time')
            ->count();

        // إضافة إجمالي أيام العمل للإحصائيات
        $attendanceStats['total_work_days'] = $totalWorkDays;

        // حساب أيام الغياب
        $attendanceStats['absent_days'] = (clone $statsQuery)
            ->where('status', 'غيــاب')
            ->count();

        // حساب المخالفات
        $attendanceStats['violation_days'] = (clone $statsQuery)
            ->where('penalty', '>', 0)
            ->count();

        // حساب التأخير
        $lateRecords = (clone $statsQuery)
            ->where('delay_minutes', '>', 0)
            ->whereNotNull('entry_time')
            ->get();

        $attendanceStats['late_days'] = $lateRecords->count();
        $attendanceStats['total_delay_minutes'] = $lateRecords->sum('delay_minutes');
        $attendanceStats['avg_delay_minutes'] = $lateRecords->count() > 0
            ? round($lateRecords->average('delay_minutes'), 1)
            : 0;
        $attendanceStats['max_delay_minutes'] = $lateRecords->max('delay_minutes') ?? 0;

        if ($user->role === 'manager') {
            // إحصائيات اليوم للمدير
            $todayStats = [
                'totalEmployees' => User::where('role', 'employee')->count(),
                'presentToday' => AttendanceRecord::whereDate('attendance_date', Carbon::today())
                    ->where('status', 'حضـور')
                    ->count(),
                'absentToday' => AttendanceRecord::whereDate('attendance_date', Carbon::today())
                    ->where('status', 'غيــاب')
                    ->count(),
                'lateToday' => AttendanceRecord::whereDate('attendance_date', Carbon::today())
                    ->where('delay_minutes', '>', 0)
                    ->count()
            ];

            // طلبات اليوم
            $todayRequests = [
                'absenceRequests' => AbsenceRequest::whereDate('absence_date', Carbon::today())->count(),
                'permissionRequests' => PermissionRequest::whereDate('created_at', Carbon::today())->count(),
                'overtimeRequests' => OverTimeRequests::whereDate('overtime_date', Carbon::today())->count(),
                'violations' => Violation::whereDate('created_at', Carbon::today())->count()
            ];

            return view('dashboard', compact('todayStats', 'todayRequests', 'attendanceStats'));
        }

        // في حالة الموظف العادي
        return view('profile.dashboard-user', compact('attendanceStats', 'salaryFiles', 'startDate', 'endDate'));
    }

    public function previewAttendance($employee_id, AttendanceReportService $reportService)
    {
        // تهيئة المتغير بقيم افتراضية
        $attendanceStats = [
            'present_days' => 0,
            'absent_days' => 0,
            'violation_days' => 0,
            'late_days' => 0,
            'total_delay_minutes' => 0,
            'avg_delay_minutes' => 0,
            'max_delay_minutes' => 0,
            'total_work_days' => 0,
            'period' => []
        ];

        $now = now();
        $recordsQuery = AttendanceRecord::where('employee_id', $employee_id);

        // تحديد الفترة حسب الفلتر
        if (request('start_date') && request('end_date')) {
            $startDate = Carbon::parse(request('start_date'))->startOfDay();
            $endDate = Carbon::parse(request('end_date'))->endOfDay();
            $recordsQuery->whereBetween('attendance_date', [$startDate, $endDate]);

            $attendanceStats['period'] = [
                'month' => $startDate->translatedFormat('F'),
                'year' => $startDate->year
            ];
        } elseif (request('month')) {
            $year = request('year') ?: $now->year;
            $startDate = Carbon::create($year, request('month'), 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            $recordsQuery->whereMonth('attendance_date', request('month'))
                ->whereYear('attendance_date', $year);

            $attendanceStats['period'] = [
                'month' => $startDate->translatedFormat('F'),
                'year' => $year
            ];
        } elseif (request('year')) {
            $startDate = Carbon::create(request('year'), 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
            $recordsQuery->whereYear('attendance_date', request('year'));

            $attendanceStats['period'] = [
                'month' => $startDate->translatedFormat('F'),
                'year' => request('year')
            ];
        } else {
            $startDate = $now->copy()->subMonth()->setDay(26)->startOfDay();
            $endDate = $now->copy()->setDay(25)->endOfDay();
            $recordsQuery->whereBetween('attendance_date', [$startDate, $endDate]);

            $attendanceStats['period'] = [
                'month' => $startDate->translatedFormat('F'),
                'year' => $startDate->year
            ];
        }

        // تطبيق فلتر الحالة
        if (request('status')) {
            $recordsQuery->where('status', request('status'));
        }

        // جساب الإحصائيات
        $statsQuery = clone $recordsQuery;

        // حساب أجمالي أيام العمل (فقط أيام الحضور والغياب)
        $totalWorkDays = (clone $statsQuery)
            ->where(function ($query) {
                $query->where('status', 'حضـور')
                    ->orWhere('status', 'غيــاب');
            })
            ->count();

        // حساب أيام الحضور
        $attendanceStats['present_days'] = (clone $statsQuery)
            ->where('status', 'حضـور')
            ->whereNotNull('entry_time')
            ->count();

        // إضافة إجمالي أيام العمل للإحصائيات
        $attendanceStats['total_work_days'] = $totalWorkDays;

        // حساب أيام الغياب
        $attendanceStats['absent_days'] = (clone $statsQuery)
            ->where('status', 'غيــاب')
            ->count();

        // حساب المخالفات
        $attendanceStats['violation_days'] = (clone $statsQuery)
            ->where('penalty', '>', 0)
            ->count();

        // حساب التأخير
        $lateRecords = (clone $statsQuery)
            ->where('delay_minutes', '>', 0)
            ->whereNotNull('entry_time')
            ->get();

        $attendanceStats['late_days'] = $lateRecords->count();
        $attendanceStats['total_delay_minutes'] = $lateRecords->sum('delay_minutes');
        $attendanceStats['avg_delay_minutes'] = $lateRecords->count() > 0
            ? round($lateRecords->average('delay_minutes'), 1)
            : 0;
        $attendanceStats['max_delay_minutes'] = $lateRecords->max('delay_minutes') ?? 0;

        // إحصائيات آخر 3 شهور
        $threeMonthsStats = [];
        $currentDate = now();

        for ($i = 0; $i < 3; $i++) {
            $monthDate = $currentDate->copy()->subMonths($i);
            $monthStart = $monthDate->copy()->startOfMonth();
            $monthEnd = $monthDate->copy()->endOfMonth();

            $monthQuery = AttendanceRecord::where('employee_id', $employee_id)
                ->whereBetween('attendance_date', [$monthStart, $monthEnd]);

            if (request('status')) {
                $monthQuery->where('status', request('status'));
            }

            // حساب أيام الحضور والغياب فقط
            $workingDaysQuery = clone $monthQuery;
            $totalWorkDays = $workingDaysQuery->where(function ($query) {
                $query->where('status', 'حضـور')
                    ->orWhere('status', 'غيــاب');
            })->count();

            $threeMonthsStats[] = [
                'month' => $monthDate->translatedFormat('F'),
                'year' => $monthDate->year,
                'total_days' => $totalWorkDays,  // تغيير هنا - فقط أيام الحضور والغياب
                'present_days' => (clone $monthQuery)->where('status', 'حضـور')->count(),
                'absent_days' => (clone $monthQuery)->where('status', 'غيــاب')->count()
            ];
        }

        // جلب السجلات مع الترقيم وحفظ الفلاتر في الروابط
        $attendanceRecords = $recordsQuery->orderBy('attendance_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        return $reportService->previewAttendance(
            $employee_id,
            $attendanceStats,
            $startDate,
            $endDate,
            $threeMonthsStats,
            $attendanceRecords
        );
    }
}
