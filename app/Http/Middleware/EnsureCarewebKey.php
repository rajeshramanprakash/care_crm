<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCarewebKey
{
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) env('CAREWEB_API_KEY', '');
        if ($expected === '') {
            return response()->json([
                'success' => false,
                'message' => 'CAREWEB_API_KEY is not configured.',
            ], 503);
        }

        $provided = (string) $request->header('X-CAREWEB-KEY', '');
        if (! hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}

