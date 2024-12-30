<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarySheet extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'file_path',
        'original_filename'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
