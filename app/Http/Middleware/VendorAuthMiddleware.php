<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VendorAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! session()->has('vendor_id') || session('login_type') !== 'vendor') {
            return redirect()->route('home')->with('error', 'Please login to access vendor dashboard.');
        }

        return $next($request);
    }
}
