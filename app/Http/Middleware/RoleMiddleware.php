<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Check if the user's role matches any of the given roles
            if (in_array($user->role, $roles)) {
                return $next($request);
            }
        }

        // Redirect to a "home" or "no-access" page if the user doesn't have the correct role
        return redirect()->route('login')->with('error', 'You do not have the required access');
    }
}
