<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OnlineStatusController extends Controller
{
    public function updateStatus(Request $request)
    {
        $user = Auth::user();
        $user->update([
            'is_online' => $request->is_online,
            'last_seen_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function getUserStatus($userId)
    {
        $user = User::findOrFail($userId);
        return response()->json([
            'is_online' => $user->is_online,
            'last_seen_at' => $user->last_seen_at
        ]);
    }
}