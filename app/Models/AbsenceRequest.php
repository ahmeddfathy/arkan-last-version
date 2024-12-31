<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AbsenceRequest extends Model
{
    protected $fillable = [
        'user_id',
        'absence_date',
        'reason',
        'manager_status',
        'manager_rejection_reason',
        'hr_status',
        'hr_rejection_reason',
        'status'
    ];

    protected $casts = [
        'absence_date' => 'date'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canRespond(User $user): bool
    {
        // HR يمكنه الرد على أي طلب
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
            return true;
        }

        // التحقق مما إذا كان المستخدم صاحب الطلب موجود في أي فريق
        $hasTeam = DB::table('team_user')
            ->where('user_id', $this->user_id)
            ->exists();

        // إذا لم يكن المستخدم في أي فريق، فقط HR يمكنه الرد
        if (!$hasTeam) {
            return false;
        }

        // التحقق من أن المستخدم لديه صلاحية الرد كمدير
        if (!$user->hasPermissionTo('manager_respond_absence_request')) {
            return false;
        }

        // التحقق من أن المستخدم مدير وأيضاً admin أو owner في الفريق
        if ($user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $team = $user->currentTeam;

            // التحقق من أن المستخدم لديه دور admin أو owner في الفريق
            $isAdminOrOwner = DB::table('team_user')
                ->join('users', 'users.id', '=', 'team_user.user_id')
                ->where('team_user.team_id', $team->id)
                ->where('team_user.user_id', $user->id)
                ->whereIn('team_user.role', ['admin', 'owner'])
                ->exists();

            return $isAdminOrOwner;
        }

        return false;
    }

    // التحقق من إمكانية إنشاء طلب
    public function canCreate(User $user): bool
    {
        return $user->hasPermissionTo('create_absence');
    }

    // التحقق من إمكانية تعديل الطلب
    public function canUpdate(User $user): bool
    {
        if (!$user->hasPermissionTo('update_absence')) {
            return false;
        }
        return $user->id === $this->user_id && $this->status === 'pending';
    }

    // التحقق من إمكانية حذف الطلب
    public function canDelete(User $user): bool
    {
        if (!$user->hasPermissionTo('delete_absence')) {
            return false;
        }
        return $user->id === $this->user_id && $this->status === 'pending';
    }

    // التحقق من إمكانية تعديل الرد
    public function canModifyResponse(User $user): bool
    {
        // نفس منطق canRespond
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
            return true;
        }

        if (!$user->hasPermissionTo('manager_respond_absence_request')) {
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
        // التحقق مما إذا كان المستخدم موجود في أي فريق
        $hasTeam = DB::table('team_user')
            ->where('user_id', $this->user_id)
            ->exists();

        // إذا لم يكن المستخدم في أي فريق، نتحقق فقط من موافقة HR
        if (!$hasTeam) {
            $this->status = $this->hr_status;
            return;
        }

        // إذا كان أحد الردود مرفوض، الطلب مرفوض
        if ($this->manager_status === 'rejected' || $this->hr_status === 'rejected') {
            $this->status = 'rejected';
            return;
        }

        // إذا كان كلا الردين موافق، الطلب موافق
        if ($this->manager_status === 'approved' && $this->hr_status === 'approved') {
            $this->status = 'approved';
            return;
        }

        // في أي حالة أخرى، الطلب معلق
        $this->status = 'pending';
    }
}
