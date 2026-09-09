<?php

namespace App\Http\Middleware;

use App\Models\B2BUser;
use Closure;
use Illuminate\Http\Request;

class B2BAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! session()->has('b2b_user_id')) {
            return redirect()->route('home')->with('error', 'Please login first');
        }

        $user = B2BUser::query()->find(session('b2b_user_id'));
        if (! $user || ! $user->isLegacy()) {
            return redirect()->route('home')->with('error', 'Unauthorized access.');
        }

        return $next($request);
    }
}

