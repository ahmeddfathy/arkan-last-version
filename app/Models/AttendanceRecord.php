<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
  protected $fillable = [
    "user_id",
    'employee_id',
    'attendance_date',
    'day',
    'status',
    'shift',
    'shift_hours',
    'entry_time',
    'exit_time',
    'delay_minutes',
    'early_minutes',
    'working_hours',
    'overtime_hours',
    'penalty',
    'notes'
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
