<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Implicitly grant "Admin" role all permissions
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if (session()->has('logged_role')) {
                if (session('logged_role') == 1) {
                    return true;
                }
            } else {
                $roleIds = explode(',', $user->role_id);
                if (in_array('1', $roleIds) && count($roleIds) === 1) {
                    return true;
                }
            }
        });
    }
}
