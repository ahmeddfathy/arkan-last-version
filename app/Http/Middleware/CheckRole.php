<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
  public function handle($request, Closure $next, ...$roles)
  {
    if (!auth()->check()) {
      return redirect('login');
    }

    if (in_array(auth()->user()->role, $roles)) {
      return $next($request);
    }

    return redirect()->route('welcome')->with('error', 'Unauthorized action.');
  }
}
