<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorporateAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! session()->has('corporate_user_id') || session('login_type') !== 'corporate') {
            return redirect()->route('corporate.login')->with('error', 'Please login to continue.');
        }

        return $next($request);
    }
}
