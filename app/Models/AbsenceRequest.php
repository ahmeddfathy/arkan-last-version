<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
class AbsenceRequest extends Model
{
    use AuthorizesRequests;
    protected $fillable = [
        'user_id',
        'absence_date',
        'status',
        'reason',
        "rejection_reason"
    ];

    protected $casts = [
        'absence_date' => 'date'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }



}
