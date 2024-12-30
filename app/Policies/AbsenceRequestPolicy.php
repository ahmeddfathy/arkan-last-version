<?php

namespace App\Policies;

use App\Models\AbsenceRequest;
use App\Models\User;

class AbsenceRequestPolicy
{
    public function update(User $user, AbsenceRequest $absenceRequest)
    {
        return $user->id === $absenceRequest->user_id && $absenceRequest->status === 'pending';
    }

    public function delete(User $user, AbsenceRequest $absenceRequest)
    {
        return $user->id === $absenceRequest->user_id && $absenceRequest->status === 'pending';
    }

    public function updateStatus(User $user, AbsenceRequest $absenceRequest)
    {
        return $user->role === 'manager';
    }
}