<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorporateEmployeeAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! session()->has('corporate_employee_id') || session('login_type') !== 'corporate_employee') {
            return redirect()->route('corporate.employee.login')->with('error', 'Please login to continue.');
        }

        return $next($request);
    }
}
