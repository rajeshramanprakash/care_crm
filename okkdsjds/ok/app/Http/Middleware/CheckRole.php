<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        $userRoles = explode(',', $request->user()->role_id);
        $roleIds = [
            'Admin' => 1,
            'Sales' => 2,
            'Sales Manager' => 3,
            'Operation Manager' => 4,
            'Operation' => 5
        ];

        if (!isset($roleIds[$role])) {
            return redirect('/')->with('error', 'Invalid role');
        }

        if (!in_array($roleIds[$role], $userRoles)) {
            return redirect('/')->with('error', 'Unauthorized access');
        }

        return $next($request);
    }
}
