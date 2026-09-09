<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BrokerAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! session()->has('broker_user_id') || session('login_type') !== 'broker') {
            return redirect()->route('broker.login')->with('error', 'Please login to continue.');
        }

        return $next($request);
    }
}
