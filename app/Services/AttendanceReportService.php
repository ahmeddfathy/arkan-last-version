<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class AttendanceReportService
{
  public function previewAttendance($employee_id, $attendanceStats, $startDate, $endDate, $threeMonthsStats, $attendanceRecords)
  {
    $user = User::where('employee_id', $employee_id)->firstOrFail();

    return view('attendance.preview', compact(
      'user',
      'attendanceStats',
      'startDate',
      'endDate',
      'threeMonthsStats',
      'attendanceRecords'
    ));
  }
}
