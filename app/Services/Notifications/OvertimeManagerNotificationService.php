<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\OverTimeRequests;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OvertimeManagerNotificationService
{
  public function notifyAboutNewRequest(OverTimeRequests $request): void
  {
    $notifyUsers = collect();

    // إضافة مدراء الفريق
    $hasTeam = DB::table('team_user')
      ->where('user_id', $request->user_id)
      ->exists();

    if ($hasTeam) {
      $teamOwners = DB::table('team_user')
        ->join('teams', 'teams.id', '=', 'team_user.team_id')
        ->where('team_user.user_id', $request->user_id)
        ->where('team_user.role', 'owner')
        ->pluck('team_user.user_id');

      $notifyUsers = $notifyUsers->merge(User::whereIn('id', $teamOwners)->get());
    }

    // إضافة HR
    $hrUsers = User::role('hr')->get();
    $notifyUsers = $notifyUsers->merge($hrUsers);

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
  }

  public function notifyAboutModification(OverTimeRequests $request): void
  {
    $notifyUsers = collect();

    // إضافة مدراء الفريق
    $hasTeam = DB::table('team_user')
      ->where('user_id', $request->user_id)
      ->exists();

    if ($hasTeam) {
      $teamOwners = DB::table('team_user')
        ->join('teams', 'teams.id', '=', 'team_user.team_id')
        ->where('team_user.user_id', $request->user_id)
        ->where('team_user.role', 'owner')
        ->pluck('team_user.user_id');

      $notifyUsers = $notifyUsers->merge(User::whereIn('id', $teamOwners)->get());
    }

    // إضافة HR
    $hrUsers = User::role('hr')->get();
    $notifyUsers = $notifyUsers->merge($hrUsers);

    foreach ($notifyUsers as $user) {
      Notification::create([
        'user_id' => $user->id,
        'type' => 'overtime_request_modified',
        'data' => [
          'message' => "{$request->user->name} has modified their overtime request",
          'request_id' => $request->id,
          'overtime_date' => $request->overtime_date,
          'has_team' => $hasTeam
        ],
        'related_id' => $request->id
      ]);
    }
  }

  public function notifyAboutDeletion(OverTimeRequests $request): void
  {
    $notifyUsers = collect();

    // إضافة مدراء الفريق
    $hasTeam = DB::table('team_user')
      ->where('user_id', $request->user_id)
      ->exists();

    if ($hasTeam) {
      $teamOwners = DB::table('team_user')
        ->join('teams', 'teams.id', '=', 'team_user.team_id')
        ->where('team_user.user_id', $request->user_id)
        ->where('team_user.role', 'owner')
        ->pluck('team_user.user_id');

      $notifyUsers = $notifyUsers->merge(User::whereIn('id', $teamOwners)->get());
    }

    // إضافة HR
    $hrUsers = User::role('hr')->get();
    $notifyUsers = $notifyUsers->merge($hrUsers);

    foreach ($notifyUsers as $user) {
      Notification::create([
        'user_id' => $user->id,
        'type' => 'overtime_request_deleted',
        'data' => [
          'message' => "{$request->user->name} has deleted their overtime request",
          'request_id' => $request->id,
          'overtime_date' => $request->overtime_date,
          'has_team' => $hasTeam
        ],
        'related_id' => $request->id
      ]);
    }
  }
}
