<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InsurerAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! session()->has('insurer_user_id') || session('login_type') !== 'insurer') {
            return redirect()->route('insurer.login')->with('error', 'Please login to continue.');
        }

        return $next($request);
    }
}
