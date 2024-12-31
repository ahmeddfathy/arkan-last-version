<?php

namespace App\Services;

use App\Models\OverTimeRequests;
use App\Services\NotificationOvertimeService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Notification;

class OverTimeRequestService
{
    protected $notificationService;

    public function __construct(NotificationOvertimeService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function getAllRequests(): LengthAwarePaginator
    {
        return OverTimeRequests::with('user')
            ->latest()
            ->paginate(10);
    }

    public function getUserRequests(int $userId, ?string $startDate = null, ?string $endDate = null): LengthAwarePaginator
    {
        $query = OverTimeRequests::where('user_id', $userId);

        if ($startDate && $endDate) {
            $query->whereBetween('overtime_date', [$startDate, $endDate]);
        }

        return $query->latest()->paginate(10);
    }

    public function getTeamRequests(int $teamId, ?string $employeeName = null, ?string $status = null, ?string $startDate = null, ?string $endDate = null): LengthAwarePaginator
    {
        $query = OverTimeRequests::query()
            ->with('user')
            ->whereHas('user', function ($q) use ($teamId) {
                $q->whereHas('teams', function ($q) use ($teamId) {
                    $q->where('teams.id', $teamId);
                });
            });

        $this->applyFilters($query, $employeeName, $status, $startDate, $endDate);

        return $query->latest()->paginate(10);
    }

    public function getAllTeamRequests(?string $employeeName = null, ?string $status = null, ?string $startDate = null, ?string $endDate = null): LengthAwarePaginator
    {
        $query = OverTimeRequests::query()
            ->with('user')
            ->whereHas('user', function ($q) {
                $q->whereHas('teams');
            });

        $this->applyFilters($query, $employeeName, $status, $startDate, $endDate);

        return $query->latest()->paginate(10);
    }

    public function getNoTeamRequests(?string $employeeName = null, ?string $status = null, ?string $startDate = null, ?string $endDate = null): LengthAwarePaginator
    {
        $query = OverTimeRequests::query()
            ->with('user')
            ->whereHas('user', function ($q) {
                $q->whereDoesntHave('teams');
            });

        $this->applyFilters($query, $employeeName, $status, $startDate, $endDate);

        return $query->latest()->paginate(10);
    }

    private function applyFilters($query, ?string $employeeName, ?string $status, ?string $startDate, ?string $endDate): void
    {
        if ($employeeName) {
            $query->whereHas('user', function ($q) use ($employeeName) {
                $q->where('name', 'like', "%{$employeeName}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('overtime_date', [$startDate, $endDate]);
        }
    }

    public function getPendingRequestsCount(int $teamId): int
    {
        return OverTimeRequests::whereHas('user', function ($q) use ($teamId) {
            $q->whereHas('teams', function ($q) use ($teamId) {
                $q->where('teams.id', $teamId);
            });
        })->where('status', 'pending')->count();
    }

    public function getAllPendingRequestsCount(): int
    {
        return OverTimeRequests::where('status', 'pending')->count();
    }

    public function createRequest(array $data): OverTimeRequests
    {
        return DB::transaction(function () use ($data) {
            $userId = $data['user_id'] ?? Auth::id();

            // التحقق من تداخل الأوقات أولاً
            $this->validateOverTimeRequest(
                $userId,
                $data['overtime_date'],
                $data['start_time'],
                $data['end_time']
            );

            $request = OverTimeRequests::create([
                'user_id' => $userId,
                'overtime_date' => $data['overtime_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'reason' => $data['reason'],
                'manager_status' => 'pending',
                'hr_status' => 'pending',
                'status' => 'pending'
            ]);

            // إرسال الإشعارات
            $notifyUsers = collect();

            // التحقق من وجود الفريق
            $hasTeam = DB::table('team_user')
                ->where('team_user.user_id', $userId)
                ->exists();

            if ($hasTeam) {
                // جلب مدراء الفريق
                $teamManagers = DB::table('team_user as tu1')
                    ->join('teams', 'teams.id', '=', 'tu1.team_id')
                    ->join('team_user as tu2', function ($join) use ($userId) {
                        $join->on('tu2.team_id', '=', 'tu1.team_id')
                            ->where('tu2.user_id', '=', $userId);
                    })
                    ->where('tu1.role', '=', 'owner')
                    ->select('tu1.user_id')
                    ->get()
                    ->pluck('user_id');

                $notifyUsers = $notifyUsers->merge(User::whereIn('id', $teamManagers)->get());
            }

            // إضافة HR
            $hrUsers = User::role('hr')->get();
            $notifyUsers = $notifyUsers->merge($hrUsers);

            // إرسال الإشعارات
            foreach ($notifyUsers as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'new_overtime_request',
                    'data' => [
                        'message' => "{$request->user->name} has submitted an overtime request",
                        'request_id' => $request->id,
                        'overtime_date' => $request->overtime_date,
                        'has_team' => $hasTeam
                    ],
                    'related_id' => $request->id
                ]);
            }

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
                $this->notificationService->notifyAboutModification($request);
            }

            return $updated;
        });
    }

    public function deleteRequest(OverTimeRequests $request): bool
    {
        return DB::transaction(function () use ($request) {
            $this->notificationService->notifyAboutDeletion($request);
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
                $this->notificationService->notifyStatusUpdate($request);
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
                $this->notificationService->notifyRequestReset($request);
            }

            return $updated;
        });
    }

    public function modifyResponse(OverTimeRequests $request, array $data): bool
    {
        return DB::transaction(function () use ($request, $data) {
            $this->notificationService->deleteExistingStatusNotifications($request);

            $updated = $request->update([
                'status' => $data['status'],
                'rejection_reason' => $data['status'] === 'rejected' ? $data['rejection_reason'] : null
            ]);

            if ($updated) {
                $this->notificationService->notifyStatusUpdate($request);
            }

            return $updated;
        });
    }

    public function calculateOvertimeHours(int $userId): float
    {
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        return OverTimeRequests::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereBetween('overtime_date', [$startOfYear, $endOfYear])
            ->get()
            ->sum(function ($request) {
                return $request->getOvertimeHours();
            });
    }

    public function updateManagerStatus(OverTimeRequests $request, array $data): bool
    {
        return DB::transaction(function () use ($request, $data) {
            $request->manager_status = $data['status'];
            $request->manager_rejection_reason = $data['status'] === 'rejected' ? $data['rejection_reason'] : null;
            $request->updateFinalStatus();
            $request->save();

            $this->notificationService->notifyStatusUpdate($request);
            return true;
        });
    }

    public function updateHrStatus(OverTimeRequests $request, array $data): bool
    {
        return DB::transaction(function () use ($request, $data) {
            $request->hr_status = $data['status'];
            $request->hr_rejection_reason = $data['status'] === 'rejected' ? $data['rejection_reason'] : null;
            $request->updateFinalStatus();
            $request->save();

            $this->notificationService->notifyStatusUpdate($request);
            return true;
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
