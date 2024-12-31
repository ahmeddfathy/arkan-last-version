<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\OverTimeRequests;
use Illuminate\Support\Facades\DB;

class NotificationOvertimeService
{
  public function createOvertimeRequestNotification(OverTimeRequests $request): void
  {
    $notifyUsers = collect();

    $hasTeam = DB::table('team_user')
      ->where('user_id', $request->user_id)
      ->exists();

    if ($hasTeam) {
      $teamOwners = DB::table('team_user')
        ->join('teams', 'teams.id', '=', 'team_user.team_id')
        ->where('team_user.user_id', $request->user_id)
        ->where('team_user.role', 'owner')
        ->pluck('user_id');

      $notifyUsers = $notifyUsers->merge(User::whereIn('id', $teamOwners)->get());
    }

    $hrUsers = User::role('hr')->pluck('id');
    $notifyUsers = $notifyUsers->merge(User::whereIn('id', $hrUsers)->get());

    foreach ($notifyUsers as $user) {
      Notification::create([
        'user_id' => $user->id,
        'type' => 'new_overtime_request',
        'data' => [
          'message' => "{$request->user->name} has submitted an overtime request",
          'request_id' => $request->id,
          'date' => $request->overtime_date->format('Y-m-d'),
          'has_team' => $hasTeam
        ],
        'related_id' => $request->id
      ]);
    }
  }

  public function createStatusUpdateNotification(OverTimeRequests $request): void
  {
    // Delete existing status notifications first
    $this->deleteStatusNotifications($request);

    // Create new notification
    Notification::create([
      'user_id' => $request->user_id,
      'type' => 'overtime-requests',
      'data' => [
        'message' => "Your overtime request has been {$request->status}",
        'request_id' => $request->id,
        'status' => $request->status,
        'rejection_reason' => $request->rejection_reason,
      ],
      'related_id' => $request->id
    ]);
  }

  public function deleteStatusNotifications(OverTimeRequests $request): void
  {
    Notification::where('related_id', $request->id)
      ->where('type', 'overtime_request_status_update')
      ->delete();
  }

  public function getUnreadCount(User $user): int
  {
    return Notification::where('user_id', $user->id)
      ->whereNull('read_at')
      ->count();
  }

  public function getUserNotifications(User $user)
  {
    return Notification::where('user_id', $user->id)
      ->orderBy('created_at', 'desc')
      ->paginate(10);
  }

  public function markAsRead(Notification $notification): void
  {
    $notification->markAsRead();
  }

  public function notifyStatusUpdate(OverTimeRequests $request): void
  {
    $this->deleteExistingStatusNotifications($request);

    $message = $this->getStatusUpdateMessage($request);

    // إشعار للموظف
    Notification::create([
      'user_id' => $request->user_id,
      'type' => 'overtime_status_update',
      'data' => [
        'message' => $message,
        'request_id' => $request->id,
        'overtime_date' => $request->overtime_date,
        'status' => $request->status
      ],
      'related_id' => $request->id
    ]);

    // إشعار للمدراء و HR إذا تم الرفض
    if ($request->status === 'rejected') {
      $this->notifyManagersAndHR($request, $message);
    }
  }

  public function notifyAboutModification(OverTimeRequests $request): void
  {
    $message = "{$request->user->name} has modified their overtime request for {$request->overtime_date}";

    $notifyUsers = $this->getManagersAndHRUsers($request);

    foreach ($notifyUsers as $user) {
      Notification::create([
        'user_id' => $user->id,
        'type' => 'overtime_request_modified',
        'data' => [
          'message' => $message,
          'request_id' => $request->id,
          'overtime_date' => $request->overtime_date
        ],
        'related_id' => $request->id
      ]);
    }
  }

  public function notifyAboutDeletion(OverTimeRequests $request): void
  {
    $message = "{$request->user->name} has deleted their overtime request for {$request->overtime_date}";

    $notifyUsers = $this->getManagersAndHRUsers($request);

    foreach ($notifyUsers as $user) {
      Notification::create([
        'user_id' => $user->id,
        'type' => 'overtime_request_deleted',
        'data' => [
          'message' => $message,
          'overtime_date' => $request->overtime_date
        ],
        'related_id' => $request->id
      ]);
    }
  }

  public function notifyRequestReset(OverTimeRequests $request): void
  {
    $message = "Your overtime request for {$request->overtime_date} has been reset to pending";

    Notification::create([
      'user_id' => $request->user_id,
      'type' => 'overtime_request_reset',
      'data' => [
        'message' => $message,
        'request_id' => $request->id,
        'overtime_date' => $request->overtime_date
      ],
      'related_id' => $request->id
    ]);
  }

  public function deleteExistingStatusNotifications(OverTimeRequests $request): void
  {
    Notification::where('related_id', $request->id)
      ->where('type', 'overtime_status_update')
      ->delete();
  }

  private function getStatusUpdateMessage(OverTimeRequests $request): string
  {
    if ($request->status === 'approved') {
      return "Your overtime request for {$request->overtime_date} has been approved";
    }

    return "Your overtime request for {$request->overtime_date} has been rejected" .
      ($request->getRejectionReason() ? ": {$request->getRejectionReason()}" : "");
  }

  private function notifyManagersAndHR(OverTimeRequests $request, string $message): void
  {
    $notifyUsers = $this->getManagersAndHRUsers($request);

    foreach ($notifyUsers as $user) {
      Notification::create([
        'user_id' => $user->id,
        'type' => 'overtime_status_update',
        'data' => [
          'message' => str_replace('Your', "{$request->user->name}'s", $message),
          'request_id' => $request->id,
          'overtime_date' => $request->overtime_date,
          'status' => $request->status
        ],
        'related_id' => $request->id
      ]);
    }
  }

  private function getManagersAndHRUsers(OverTimeRequests $request)
  {
    $notifyUsers = collect();

    // جلب مدراء الفريق
    $teamManagers = DB::table('team_user as tu1')
      ->join('teams', 'teams.id', '=', 'tu1.team_id')
      ->join('team_user as tu2', function ($join) use ($request) {
        $join->on('tu2.team_id', '=', 'tu1.team_id')
          ->where('tu2.user_id', '=', $request->user_id);
      })
      ->where('tu1.role', '=', 'owner')
      ->select('tu1.user_id')
      ->get()
      ->pluck('user_id');

    $notifyUsers = $notifyUsers->merge(User::whereIn('id', $teamManagers)->get());

    // إضافة HR
    $hrUsers = User::role('hr')->get();
    $notifyUsers = $notifyUsers->merge($hrUsers);

    return $notifyUsers->unique('id');
  }
}
