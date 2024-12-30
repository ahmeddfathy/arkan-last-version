<?php

namespace App\Services;

use App\Models\PermissionRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\ViolationService;
use App\Services\NotificationPermissionService;

class PermissionRequestService
{
    protected $violationService;
    protected $notificationService;

    public function __construct(
        ViolationService $violationService,
        NotificationPermissionService $notificationService
    ) {
        $this->violationService = $violationService;
        $this->notificationService = $notificationService;
    }


    const MONTHLY_LIMIT_MINUTES = 180;

    public function getAllRequests($filters = []): LengthAwarePaginator
    {
        $user = Auth::user();
        $query = PermissionRequest::with('user');

        if (!empty($filters['employee_name'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['employee_name'] . '%');
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($user->role === 'manager') {
            return $query->latest()->paginate(10);
        }

        return $query->where('user_id', $user->id)
            ->latest()
            ->paginate(10);
    }

    public function createRequest(array $data): array
    {
        $userId = Auth::id();
        $validation = $this->validateTimeRequest(
            $userId,
            $data['departure_time'],
            $data['return_time']
        );

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }

        $remainingMinutes = $this->getRemainingMinutes($userId);

        $request = PermissionRequest::create([
            'user_id' => $userId,
            'departure_time' => $data['departure_time'],
            'return_time' => $data['return_time'],
            'minutes_used' => $validation['duration'],
            'reason' => $data['reason'],
            'remaining_minutes' => $remainingMinutes - $validation['duration'],
            'status' => 'pending',
            'returned_on_time' => false,
        ]);

        $this->notificationService->createPermissionRequestNotification($request);

        return ['success' => true];
    }

    public function updateRequest(PermissionRequest $request, array $data): array
    {
        $userId = Auth::id();
        $validation = $this->validateTimeRequest(
            $userId,
            $data['departure_time'],
            $data['return_time'],
            $request->id
        );

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }

        $request->update([
            'departure_time' => $data['departure_time'],
            'return_time' => $data['return_time'],
            'reason' => $data['reason'],
            'minutes_used' => $validation['duration'],
        ]);

        $this->notificationService->notifyPermissionModified($request);

        return ['success' => true];
    }

    public function updateStatus(PermissionRequest $request, array $data): array
    {
        $request->update([
            'status' => $data['status'],
            'rejection_reason' => $data['status'] === 'rejected' ? $data['rejection_reason'] : null,
        ]);

        $this->notificationService->createPermissionStatusUpdateNotification($request);

        return ['success' => true];
    }

    public function updateReturnStatus(PermissionRequest $request, int $returnStatus): array
    {
        $request->update(['returned_on_time' => $returnStatus]);
        $this->violationService->handleReturnViolation($request, $returnStatus);

        return ['success' => true];
    }

    public function getRemainingMinutes(int $userId): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $usedMinutes = PermissionRequest::where('user_id', $userId)
            ->whereBetween('departure_time', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['pending', 'approved'])
            ->sum('minutes_used');

        return max(0, self::MONTHLY_LIMIT_MINUTES - $usedMinutes);
    }

    private function validateTimeRequest(int $userId, string $departureTime, string $returnTime, ?int $excludeRequestId = null): array
    {
        if (Carbon::parse($returnTime) <= Carbon::parse($departureTime)) {
            return [
                'valid' => false,
                'message' => 'Return time must be after departure time.'
            ];
        }

        $duration = Carbon::parse($departureTime)->diffInMinutes(Carbon::parse($returnTime));
        $remainingMinutes = $this->getRemainingMinutes($userId);

        if ($duration > $remainingMinutes) {
            return [
                'valid' => false,
                'message' => "Cannot request more than {$remainingMinutes} minutes remaining."
            ];
        }

        if ($this->hasTimeOverlap($userId, $departureTime, $returnTime, $excludeRequestId)) {
            return [
                'valid' => false,
                'message' => 'You already have a permission request during this time period.'
            ];
        }

        return ['valid' => true, 'duration' => $duration];
    }

    private function hasTimeOverlap(int $userId, string $departureTime, string $returnTime, ?int $excludeRequestId = null): bool
    {
        $query = PermissionRequest::where('user_id', $userId)
            ->where(function ($query) use ($departureTime, $returnTime) {
                $query->where('departure_time', '<=', $returnTime)
                    ->where('return_time', '>=', $departureTime);
            })
            ->whereIn('status', ['pending', 'approved']);

        if ($excludeRequestId) {
            $query->where('id', '!=', $excludeRequestId);
        }

        return $query->exists();
    }


    public function createRequestForUser(int $userId, array $data)
    {
        $departureTime = Carbon::parse($data['departure_time']);
        $returnTime = Carbon::parse($data['return_time']);
        $durationMinutes = $departureTime->diffInMinutes($returnTime);
        $remainingMinutes = $this->getRemainingMinutes($userId);

        if ($durationMinutes > $remainingMinutes) {
            return [
                'success' => false,
                'message' => "Cannot request more than {$remainingMinutes} minutes remaining."
            ];
        }

        PermissionRequest::create([
            'user_id' => $userId,
            'departure_time' => $data['departure_time'],
            'return_time' => $data['return_time'],
            'minutes_used' => $durationMinutes,
            'reason' => $data['reason'],
            'remaining_minutes' => $remainingMinutes - $durationMinutes,
            'status' => 'pending',
            'returned_on_time' => false,
        ]);

        return ['success' => true];
    }



    public function resetStatus(PermissionRequest $request)
    {
        $request->update([
            'status' => 'pending',
            'rejection_reason' => null
        ]);

        $this->notificationService->notifyManagerResponseDeleted($request);

        return $request;
    }

    public function modifyResponse(PermissionRequest $request, array $data)
    {
        $request->update([
            'status' => $data['status'],
            'rejection_reason' => $data['status'] === 'rejected' ? $data['rejection_reason'] : null
        ]);

        $this->notificationService->notifyManagerResponseModified($request);

        return $request;
    }



    public function getUserRequestsAndLimits()
    {
        return $this->getAllRequests();
    }





    public function deleteRequest(PermissionRequest $request)
    {
        $this->notificationService->notifyPermissionDeleted($request);
        $request->delete();

        return ['success' => true];
    }
}
