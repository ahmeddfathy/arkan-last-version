<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PermissionRequest extends Model
{
    protected $fillable = [
        'user_id',
        'departure_time',
        'return_time',
        'returned_on_time',
        'minutes_used',
        'remaining_minutes',
        'status',
        'rejection_reason',
        'reason',

    ];

    protected $casts = [
        'request_datetime' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function violations()
    {
        return $this->hasMany(Violation::class, 'permission_requests_id');
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Get the return status label.
     */
    public function getReturnStatusLabel(): string
    {
        return match($this->returned_on_time) {
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
        $totalAllowed = 180;
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $usedMinutes = self::where('user_id', $this->user_id)
            ->whereBetween('departure_time', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->sum('minutes_used');

        return $totalAllowed - $usedMinutes;
    }



}

