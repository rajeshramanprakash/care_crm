<?php

namespace App\Http\Middleware;

use App\Support\CustomerPortal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (CustomerPortal::current() === null) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Please login again.'], 401);
            }

            return redirect()->route('home')->with('error', 'Please login to access customer dashboard.');
        }

        return $next($request);
    }
}
