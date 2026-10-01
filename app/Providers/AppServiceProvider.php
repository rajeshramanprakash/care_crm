<?php

namespace App\Providers;

use App\Models\CustomerChatMessage;
use App\Models\DoctorRequest;
use App\Models\JobRequest;
use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\Vendor;
use App\Observers\CustomerChatMessageObserver;
use App\Observers\LeadObserver;
use App\Observers\OperationLeadObserver;
use App\Services\B2BCorporateChatService;
use App\Services\BulkPricing\BulkPricingRuleApplier;
use App\Services\UserAssignmentService;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
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

        // Prices / services are saved after the row is created, so bulk pricing rules run once the request finishes.
        foreach ([Vendor::class, JobRequest::class, DoctorRequest::class] as $providerModel) {
            $providerModel::created(function ($model) {
                $this->app->terminating(fn () => BulkPricingRuleApplier::applyToNewRegistration($model));
            });
        }

        // Backup for servers where cron (schedule:run) is not running: start / revert due temporary rules
        // before the page is built, at most once a minute.
        if (! $this->app->runningInConsole()) {
            Event::listen(RouteMatched::class, function () {
                if (! Cache::add('bulk-pricing-tick', 1, 60)) {
                    return;
                }
                try {
                    Artisan::call('pricing:revert-temporary');
                } catch (\Throwable $e) {
                    report($e);
                }
            });
        }

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
