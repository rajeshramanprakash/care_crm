<?php

namespace App\Providers;

use App\Models\CustomerChatMessage;
use App\Models\Lead;
use App\Models\OperationLead;
use App\Observers\CustomerChatMessageObserver;
use App\Observers\LeadObserver;
use App\Observers\OperationLeadObserver;
use App\Services\B2BCorporateChatService;
use App\Services\UserAssignmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        Lead::observe(LeadObserver::class);
        OperationLead::observe(OperationLeadObserver::class);
        CustomerChatMessage::observe(CustomerChatMessageObserver::class);

        View::composer([
            'admin.layouts.sidebar',
            'sales.layouts.sidebar',
            'manager.layouts.sidebar',
            'operation.layouts.sidebar',
            'operation_manager.layouts.sidebar',
        ], function ($view) {
            $user = Auth::user();
            $show = $user && app(B2BCorporateChatService::class)->staffHasCorporatePartners($user);
            $view->with('showB2bCorporateStaffChat', $show);
        });

        // Local: ensure storage directories exist and are writable
        if (app()->environment('local')) {
            $storagePath = storage_path();
            $dirs = [
                $storagePath . '/logs',
                $storagePath . '/framework/cache/data',
                $storagePath . '/framework/sessions',
                $storagePath . '/framework/views',
                $storagePath . '/app/public',
                base_path('bootstrap/cache'),
            ];
            foreach ($dirs as $dir) {
                if (!File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }
            }
        }
    }
}
