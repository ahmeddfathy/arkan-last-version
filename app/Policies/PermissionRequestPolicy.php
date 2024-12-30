<?php

namespace App\Policies;

use App\Models\PermissionRequest;
use App\Models\User;

class PermissionRequestPolicy
{
    public function update(User $user, PermissionRequest $permissionRequest)
    {
        return $user->id === $permissionRequest->user_id && $permissionRequest->status === 'pending';
    }

    public function delete(User $user, PermissionRequest $permissionRequest)
    {
        return $user->id === $permissionRequest->user_id && $permissionRequest->status === 'pending';
    }

    public function updateStatus(User $user, PermissionRequest $permissionRequest)
    {
        return $user->role === 'manager';
    }
}