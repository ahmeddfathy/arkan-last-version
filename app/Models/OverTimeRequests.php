<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class OverTimeRequests extends Model
{
    protected $fillable = [
        'user_id',
        'overtime_date',
        'start_time',
        'end_time',
        'reason',
        'manager_status',
        'manager_rejection_reason',
        'hr_status',
        'hr_rejection_reason',
        'status'
    ];

    protected $casts = [
        'overtime_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canRespond(User $user): bool
    {
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_overtime_request')) {
            return true;
        }

        if (
            $user->hasRole(['team_leader', 'department_manager', 'company_manager']) &&
            $user->hasPermissionTo('manager_respond_overtime_request')
        ) {
            return true;
        }

        return false;
    }

    public function canUpdate(User $user): bool
    {
        return $user->id === $this->user_id &&
            $this->status === 'pending' &&
            $user->hasPermissionTo('update_overtime');
    }

    public function canDelete(User $user): bool
    {
        return $user->id === $this->user_id &&
            $this->status === 'pending' &&
            $user->hasPermissionTo('delete_overtime');
    }

    public function getFormattedDuration(): string
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);
        $minutes = $startTime->diffInMinutes($endTime);
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%d:%02d', $hours, $remainingMinutes);
    }

    public function getOvertimeHours(): float
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);
        return $startTime->diffInMinutes($endTime) / 60;
    }

    public function updateFinalStatus(): void
    {
        if ($this->manager_status === 'rejected' || $this->hr_status === 'rejected') {
            $this->status = 'rejected';
        } elseif ($this->manager_status === 'approved' && $this->hr_status === 'approved') {
            $this->status = 'approved';
        } else {
            $this->status = 'pending';
        }
    }

    public function getRejectionReason(): ?string
    {
        if ($this->status !== 'rejected') {
            return null;
        }

        return $this->manager_status === 'rejected'
            ? $this->manager_rejection_reason
            : $this->hr_rejection_reason;
    }
}
