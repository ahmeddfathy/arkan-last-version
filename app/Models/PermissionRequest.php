<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PermissionRequest extends Model
{
  protected $fillable = [
    'user_id',
    'departure_time',
    'return_time',
    'returned_on_time',
    'minutes_used',
    'remaining_minutes',
    'reason',
    'manager_status',
    'manager_rejection_reason',
    'hr_status',
    'hr_rejection_reason',
    'status'
  ];

  protected $casts = [
    'departure_time' => 'datetime',
    'return_time' => 'datetime'
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function violations()
  {
    return $this->hasMany(Violation::class, 'permission_requests_id');
  }

  // التحقق من إمكانية الرد على الطلب
  public function canRespond(User $user): bool
  {
    // HR يمكنه الرد على أي طلب
    if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
      return true;
    }

    // المدير يمكنه الرد فقط على طلبات فريقه
    if ($user->hasPermissionTo('manager_respond_permission_request')) {
      // التحقق من أن صاحب الطلب في فريق
      if ($this->user && $this->user->teams()->exists()) {
        return DB::table('team_user')
          ->where('user_id', $user->id)
          ->where('team_id', $this->user->team_id)
          ->where(function ($query) {
            $query->where('role', 'admin')
              ->orWhere('role', 'owner');
          })
          ->exists();
      }
    }

    return false;
  }

  // التحقق من إمكانية إنشاء طلب
  public function canCreate(User $user): bool
  {
    return $user->hasPermissionTo('create_permission');
  }

  // التحقق من إمكانية تعديل الطلب
  public function canUpdate(User $user): bool
  {
    if (!$user->hasPermissionTo('update_permission')) {
      return false;
    }
    return $user->id === $this->user_id && $this->status === 'pending';
  }

  // التحقق من إمكانية حذف الطلب
  public function canDelete(User $user): bool
  {
    if (!$user->hasPermissionTo('delete_permission')) {
      return false;
    }
    return $user->id === $this->user_id && $this->status === 'pending';
  }

  // التحقق من إمكانية تعديل الرد
  public function canModifyResponse(User $user): bool
  {
    // نفس منطق canRespond
    if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
      return true;
    }

    if (!$user->hasPermissionTo('manager_respond_permission_request')) {
      return false;
    }

    if ($user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
      return DB::table('team_user')
        ->where('user_id', $user->id)
        ->where('team_id', $this->user->team_id)
        ->where(function ($query) {
          $query->where('role', 'admin')
            ->orWhere('role', 'owner');
        })
        ->exists();
    }

    return false;
  }

  // تحديث حالة المدير
  public function updateManagerStatus(string $status, ?string $rejectionReason = null): void
  {
    $this->manager_status = $status;
    $this->manager_rejection_reason = $rejectionReason;
    $this->updateFinalStatus();
    $this->save();
  }

  // تحديث حالة HR
  public function updateHrStatus(string $status, ?string $rejectionReason = null): void
  {
    $this->hr_status = $status;
    $this->hr_rejection_reason = $rejectionReason;
    $this->updateFinalStatus();
    $this->save();
  }

  // تحديث الحالة النهائية
  public function updateFinalStatus(): void
  {
    // للموظفين الذين ليس لديهم فريق، نعتمد على رد HR فقط
    if ($this->user && !$this->user->teams()->exists()) {
      if ($this->hr_status === 'rejected') {
        $this->status = 'rejected';
      } elseif ($this->hr_status === 'approved') {
        $this->status = 'approved';
      } else {
        $this->status = 'pending';
      }
    } else {
      // للموظفين في الفرق، نحتاج موافقة المدير و HR
      if ($this->manager_status === 'rejected' || $this->hr_status === 'rejected') {
        $this->status = 'rejected';
      } elseif ($this->manager_status === 'approved' && $this->hr_status === 'approved') {
        $this->status = 'approved';
      } else {
        $this->status = 'pending';
      }
    }
  }

  // الدوال الخاصة بطلبات الاستئذان

  public function isApproved(): bool
  {
    return $this->status === 'approved';
  }

  public function getReturnStatusLabel(): string
  {
    return match ($this->returned_on_time) {
      0 => 'Not Specified',
      1 => 'Returned On Time',
      2 => 'Did Not Return On Time',
      default => 'N/A'
    };
  }

  public function calculateMinutesUsed()
  {
    return $this->departure_time->diffInMinutes($this->return_time);
  }

  public function calculateRemainingMinutes()
  {
    $totalAllowed = 180; // 3 ساعات في الشهر
    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth = Carbon::now()->endOfMonth();

    $usedMinutes = self::where('user_id', $this->user_id)
      ->whereBetween('departure_time', [$startOfMonth, $endOfMonth])
      ->where('status', 'approved')
      ->sum('minutes_used');

    return $totalAllowed - $usedMinutes;
  }
}
