<?php

namespace App\Services;

use App\Models\OverTimeRequests;
use App\Services\Notifications\OvertimeEmployeeNotificationService;
use App\Services\Notifications\OvertimeManagerNotificationService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OverTimeRequestService
{
    protected $employeeNotificationService;
    protected $managerNotificationService;

    public function __construct(
        OvertimeEmployeeNotificationService $employeeNotificationService,
        OvertimeManagerNotificationService $managerNotificationService
    ) {
        $this->employeeNotificationService = $employeeNotificationService;
        $this->managerNotificationService = $managerNotificationService;
    }

    public function getAllRequests(): LengthAwarePaginator
    {
        return OverTimeRequests::with('user')
            ->latest()
            ->paginate(10);
    }

    public function getUserRequests(): LengthAwarePaginator
    {
        $user = Auth::user();
        $query = OverTimeRequests::query();

        if ($user->role === 'manager') {
            $query->with('user');
        } else {
            $query->where('user_id', $user->id);
        }

        return $query->latest()->paginate(10);
    }

    public function getFilteredRequests(?string $employeeName = null, ?string $status = null): LengthAwarePaginator
    {
        return OverTimeRequests::query()
            ->with('user')
            ->when($employeeName, function ($query) use ($employeeName) {
                $query->whereHas('user', function ($q) use ($employeeName) {
                    $q->where('name', 'like', "%{$employeeName}%");
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10);
    }

    public function createRequest(array $data): OverTimeRequests
    {
        return DB::transaction(function () use ($data) {
            $userId = $data['user_id'] ?? Auth::id();

            $this->validateOverTimeRequest(
                $userId,
                $data['overtime_date'],
                $data['start_time'],
                $data['end_time']
            );

            $request = OverTimeRequests::create([
                'user_id' => $userId,
                'overtime_date' => $data['overtime_date'],
                'reason' => $data['reason'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => 'pending',
            ]);

            $this->managerNotificationService->notifyAboutNewRequest($request);

            return $request;
        });
    }

    public function update(OverTimeRequests $request, array $data): bool
    {
        return DB::transaction(function () use ($request, $data) {
            $this->validateOverTimeRequest(
                $request->user_id,
                $data['overtime_date'],
                $data['start_time'],
                $data['end_time'],
                $request->id
            );

            $updated = $request->update([
                'overtime_date' => $data['overtime_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'reason' => $data['reason']
            ]);

            if ($updated) {
                $this->managerNotificationService->notifyAboutModification($request);
            }

            return $updated;
        });
    }

    public function deleteRequest(OverTimeRequests $request): bool
    {
        return DB::transaction(function () use ($request) {
            $this->managerNotificationService->notifyAboutDeletion($request);
            return $request->delete();
        });
    }

    public function updateStatus(OverTimeRequests $request, array $data): bool
    {
        return DB::transaction(function () use ($request, $data) {
            $updated = $request->update([
                'status' => $data['status'],
                'rejection_reason' => $data['status'] === 'rejected' ? $data['rejection_reason'] : null
            ]);

            if ($updated) {
                $this->employeeNotificationService->notifyStatusUpdate($request);
            }

            return $updated;
        });
    }

    public function resetStatus(OverTimeRequests $request): bool
    {
        return DB::transaction(function () use ($request) {
            $updated = $request->update([
                'status' => 'pending',
                'rejection_reason' => null
            ]);

            if ($updated) {
                $this->employeeNotificationService->notifyRequestReset($request);
            }

            return $updated;
        });
    }

    public function modifyResponse(OverTimeRequests $request, array $data): bool
    {
        return DB::transaction(function () use ($request, $data) {
            $this->employeeNotificationService->deleteExistingStatusNotifications($request);

            $updated = $request->update([
                'status' => $data['status'],
                'rejection_reason' => $data['status'] === 'rejected' ? $data['rejection_reason'] : null
            ]);

            if ($updated) {
                $this->employeeNotificationService->notifyStatusUpdate($request);
            }

            return $updated;
        });
    }

    public function calculateOvertimeHours(int $userId): float
    {
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        $overtimeRequests = OverTimeRequests::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereBetween('overtime_date', [$startOfYear, $endOfYear])
            ->get();

        return $overtimeRequests->sum(function ($request) {
            $startTime = Carbon::parse($request->start_time);
            $endTime = Carbon::parse($request->end_time);
            return $startTime->diffInMinutes($endTime) / 60;
        });
    }

    protected function validateOverTimeRequest(
        int $userId,
        string $overtimeDate,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): void {
        $requestDate = Carbon::parse($overtimeDate);

        $query = OverTimeRequests::where('user_id', $userId)
            ->whereYear('overtime_date', $requestDate->year)
            ->whereMonth('overtime_date', $requestDate->month);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $overlappingRequest = $query->where(function ($q) use ($overtimeDate, $startTime, $endTime) {
            $q->where('overtime_date', $overtimeDate)
              ->where(function ($timeQuery) use ($startTime, $endTime) {
                  $timeQuery->where(function ($q) use ($startTime, $endTime) {
                      $q->where('start_time', '<=', $startTime)
                        ->where('end_time', '>', $startTime);
                  })
                  ->orWhere(function ($q) use ($startTime, $endTime) {
                      $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>=', $endTime);
                  })
                  ->orWhere(function ($q) use ($startTime, $endTime) {
                      $q->where('start_time', '>=', $startTime)
                        ->where('end_time', '<=', $endTime);
                  });
              });
        })->first();

        if ($overlappingRequest) {
            throw new \Exception(
                'An overtime request already exists that overlaps with this time period. ' .
                'Existing request: ' . $overlappingRequest->overtime_date .
                ' (' . $overlappingRequest->start_time . ' - ' . $overlappingRequest->end_time . ')'
            );
        }
    }
}
