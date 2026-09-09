<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        if (!Auth::check()) {
            return redirect()->route('home')->with('error', 'Please log in first');
        }

        $user = Auth::user();
        $loggedRole = session('logged_role');

        if (!$loggedRole) {
            Log::error('No logged role found for user: ' . $user->id);
            return redirect()->route('home')->with('error', 'No role selected. Please log in again.');
        }

        $userRole = Role::find($loggedRole);

        if (!$userRole) {
            Log::error('Role not found: ' . $loggedRole . ' for user: ' . $user->id);
            return redirect()->route('home')->with('error', 'Invalid role. Please log in again.');
        }

        if ($userRole->name !== $role) {
            Log::warning('Role mismatch. Required: ' . $role . ', User has: ' . $userRole->name);
            return redirect($this->getDashboardRouteForRole($userRole->name))
                ->with('error', 'Unauthorized access to this section');
        }

        return $next($request);
    }

    /**
     * Get the dashboard route for a given role
     *
     * @param string $role
     * @return string
     */
    private function getDashboardRouteForRole($role)
    {
        $routes = [
            'Admin' => 'admin.dashboard',
            'Sales' => 'sales.dashboard',
            'Sales Manager' => 'manager.dashboard',
            'Operation Manager' => 'operation-manager.dashboard',
            'Operation' => 'operation.dashboard',
            'B2B' => 'b2b.dashboard'
        ];

        return route($routes[$role] ?? 'home');
    }
}
