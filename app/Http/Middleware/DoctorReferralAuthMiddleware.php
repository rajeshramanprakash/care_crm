<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DoctorReferralAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! session()->has('doctor_referral_user_id')) {
            return redirect()->route('doctor_referral.login')->with('error', 'Please login first.');
        }

        return $next($request);
    }
}
