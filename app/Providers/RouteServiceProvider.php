<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // Throttle runs before route middleware (e.g. auth:sanctum), so $request->user() is
            // usually null for Bearer API calls — those were all bucketed by IP (60/min total).
            $userId = $request->user()?->id;
            if ($userId) {
                return Limit::perMinute(300)->by('user:'.$userId);
            }
            $token = $request->bearerToken();
            if ($token) {
                return Limit::perMinute(300)->by('token:'.sha1($token));
            }

            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
