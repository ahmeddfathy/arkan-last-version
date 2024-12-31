<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;

class OnlineStatusController extends Controller
{
  public function updateStatus(Request $request)
  {
    $user = Auth::user();
    $user->last_seen = Carbon::now();
    $user->save();

    return response()->json(['success' => true]);
  }

  public function getOnlineUsers()
  {
    $users = User::where('last_seen', '>=', Carbon::now()->subMinutes(5))
      ->get(['id', 'name', 'last_seen']);

    return response()->json($users);
  }
}
