<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class B2BReferenceAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! session()->has('b2b_reference_user_id')) {
            return redirect()->route('home')->with('error', 'Please login first.');
        }

        return $next($request);
    }
}
