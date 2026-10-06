<?php

namespace App\Http\Middleware;

use App\Support\SubAdminPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubAdminPermissionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! SubAdminPermissions::isSubAdminSession()) {
            return $next($request);
        }

        $routeName = (string) $request->route()?->getName();
        $permission = SubAdminPermissions::requiredFor($request);

        if ($permission !== null && $user->can($permission)) {
            return $next($request);
        }

        if ($routeName === 'subadmin.dashboard' && ! $request->expectsJson()) {
            $landing = SubAdminPermissions::landingRoute($user);
            if ($landing !== null && $landing !== 'subadmin.dashboard') {
                return redirect()->route($landing);
            }
        }

        $message = $permission === null
            ? 'Ye page Sub Admin ke liye available nahi hai.'
            : 'Aapko is page/action ki permission nahi hai. Admin se permission lein.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        $hasAnyPage = SubAdminPermissions::landingRoute($user) !== null;
        if ($routeName === 'subadmin.dashboard' && ! $hasAnyPage) {
            $message = 'Aapko abhi kisi bhi module ki permission nahi di gayi hai. Admin se permission lein.';
        }

        return response()->view('subadmin.no_permission', [
            'message' => $message,
            'homeUrl' => $hasAnyPage ? route(SubAdminPermissions::landingRoute($user)) : null,
        ], 403);
    }
}
