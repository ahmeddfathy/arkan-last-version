<?php

namespace App\Services;

use App\Models\Violation;
use App\Models\PermissionRequest;

class ViolationService
{
    public function handleReturnViolation(PermissionRequest $request, int $returnStatus): void
    {
        
        Violation::where('permission_requests_id', $request->id)
            ->where('reason', 'Did not return on time from approved leave')
            ->delete();

        // Create new violation only if marked as not returned
        if ($returnStatus === 2) {
            Violation::create([
                'user_id' => $request->user_id,
                'permission_requests_id' => $request->id,
                'reason' => 'Did not return on time from approved leave',
                'manager_mistake' => false
            ]);
        }
    }
}
