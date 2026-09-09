<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VendorWalletCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $auth_user =  Auth::guard('Vendor')->user();
        if ((float) $auth_user->wallet > -199999) {
            return $next($request);
        } else {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => "Please login to wallet your pending amount is very low"]);
            return redirect()->back();
        }
    }
}
