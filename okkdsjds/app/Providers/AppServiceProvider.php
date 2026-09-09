<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\UserAssignmentService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('user-assignment', function ($app) {
            return new UserAssignmentService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
