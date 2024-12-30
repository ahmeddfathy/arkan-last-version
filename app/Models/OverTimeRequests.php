<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverTimeRequests extends Model
{
 protected $fillable = [
        'user_id',
        'overtime_date',
        'status',
        'reason',
        'start_time',
        'end_time',
        'rejection_reason',
    ];



    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
