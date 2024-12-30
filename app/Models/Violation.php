<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Violation extends Model
{
    protected $fillable = [
        'user_id',
        'permission_requests_id',
        'reason',
        'manager_mistake'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function permissionRequest(): BelongsTo
    {
        return $this->belongsTo(PermissionRequest::class, 'permission_requests_id');
    }
}
