<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceAuthHeader
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->hasHeader('Authorization') && $request->hasHeader('X-Auth-Token')) {
            $token = $request->header('X-Auth-Token');
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
