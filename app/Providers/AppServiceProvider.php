<?php

namespace App\Providers;

use App\Models\CallDetails;
use App\Models\CallLog;
use App\Models\CustomerChatMessage;
use App\Models\CustomerFeedback;
use App\Models\DoctorRequest;
use App\Models\JobRequest;
use App\Models\Lead;
use App\Models\LeadStatusRemark;
use App\Models\OperationDeploymentDetails;
use App\Models\OperationLead;
use App\Models\OperationLeadStatusRemark;
use App\Models\PaymentInvoice;
use App\Models\ReceivedPayment;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Vendor;
use App\Models\WhatsAppMessage;
use App\Observers\CustomerChatMessageObserver;
use App\Observers\LeadObserver;
use App\Observers\OperationLeadObserver;
use App\Services\B2BCorporateChatService;
use App\Services\BulkPricing\BulkPricingRuleApplier;
use App\Services\UserAssignmentService;
use App\Support\LeadAi\LeadAiAutoTrigger;
use App\Support\LeadAi\LeadContext;
use App\Support\SubAdminActivityRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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

        $this->app->singleton(SubAdminActivityRecorder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Lead::observe(LeadObserver::class);
        OperationLead::observe(OperationLeadObserver::class);
        CustomerChatMessage::observe(CustomerChatMessageObserver::class);

        Event::listen(['eloquent.created: *', 'eloquent.updated: *', 'eloquent.deleted: *'], function (string $eventName, array $data) {
            $recorder = $this->app->make(SubAdminActivityRecorder::class);
            if ($recorder->isActive() && ($data[0] ?? null) instanceof Model) {
                $recorder->capture(Str::between($eventName, 'eloquent.', ':'), $data[0]);
            }
        });

        // Prices / services are saved after the row is created, so bulk pricing rules run once the request finishes.
        foreach ([Vendor::class, JobRequest::class, DoctorRequest::class] as $providerModel) {
            $providerModel::created(function ($model) {
                $this->app->terminating(fn () => BulkPricingRuleApplier::applyToNewRegistration($model));
            });
        }

        $this->registerLeadAiTriggers();

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

            // AI lead review sweep without cron: at most every 30 min, after the response of any page is sent.
            Event::listen(RouteMatched::class, function () {
                if (! LeadAiAutoTrigger::enabled() || ! Cache::add('lead-ai-sweep', 1, 1800)) {
                    return;
                }
                $this->app->terminating(function () {
                    try {
                        Artisan::call('leads:ai-analyze', ['--limit' => 3, '--hours' => 2, '--gap' => max(1, (int) config('services.gemini.lead_ai_auto_gap', 2))]);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                });
            });

            // Older leads without a review: a few every 10 min until all leads from the last N days are covered.
            Event::listen(RouteMatched::class, function () {
                $days = (int) config('services.gemini.lead_ai_backfill_days', 90);
                if ($days <= 0 || ! LeadAiAutoTrigger::enabled() || ! Cache::add('lead-ai-backfill', 1, 600)) {
                    return;
                }
                $this->app->terminating(function () use ($days) {
                    try {
                        Artisan::call('leads:ai-analyze', ['--backfill' => $days, '--limit' => max(1, (int) config('services.gemini.lead_ai_backfill_batch', 4))]);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                });
            });

            // Lead activity that came in while its review was running / inside the gap: re-run after the next page load.
            Event::listen(RouteMatched::class, function () {
                if (! LeadAiAutoTrigger::enabled() || ! LeadAiAutoTrigger::hasPending() || ! Cache::add('lead-ai-pending-tick', 1, 60)) {
                    return;
                }
                $this->app->terminating(function () {
                    try {
                        LeadAiAutoTrigger::runPending();
                    } catch (\Throwable $e) {
                        report($e);
                    }
                });
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

    /** New call / WhatsApp / remark / status / deployment / feedback → AI lead review refreshes in the background. */
    private function registerLeadAiTriggers(): void
    {
        $changed = fn ($model) => $model->wasRecentlyCreated || array_diff(array_keys($model->getChanges()), ['updated_at']) !== [];

        Lead::saved(function (Lead $lead) use ($changed) {
            if ($changed($lead)) {
                LeadAiAutoTrigger::lead('sales', $lead->id);
            }
        });
        OperationLead::saved(function (OperationLead $lead) use ($changed) {
            if ($changed($lead)) {
                LeadAiAutoTrigger::lead('operation', $lead->id);
            }
        });

        LeadStatusRemark::saved(fn ($r) => LeadAiAutoTrigger::lead('sales', $r->lead_id));
        OperationLeadStatusRemark::saved(fn ($r) => LeadAiAutoTrigger::lead('operation', $r->operation_lead_id));
        OperationDeploymentDetails::saved(fn ($d) => LeadAiAutoTrigger::lead('operation', $d->operation_lead_id));
        CustomerFeedback::saved(fn ($f) => LeadAiAutoTrigger::lead('operation', $f->operation_lead_id));
        PaymentInvoice::saved(fn ($i) => LeadAiAutoTrigger::lead('operation', $i->operation_lead_id));
        ReceivedPayment::saved(fn ($p) => LeadAiAutoTrigger::lead('operation', $p->operation_lead_id));
        SupportTicket::saved(fn ($t) => LeadAiAutoTrigger::number($t->customer_contact_no));
        SupportTicketMessage::created(fn ($m) => LeadAiAutoTrigger::number(optional($m->ticket)->customer_contact_no));
        WhatsAppMessage::created(fn ($m) => LeadAiAutoTrigger::number($m->msg_from));

        CallDetails::saved(function (CallDetails $call) {
            if (LeadContext::normalizeStatus((string) $call->call_status) === 'ringing') {
                return;
            }
            if ($call->lead_id && in_array($call->call_for, ['lead', null], true)) {
                LeadAiAutoTrigger::lead('sales', $call->lead_id);
            } elseif ($call->lead_id && $call->call_for === 'operation_lead') {
                LeadAiAutoTrigger::lead('operation', $call->lead_id);
            } else {
                LeadAiAutoTrigger::number($call->caller_id_number);
            }
        });
        CallLog::saved(function (CallLog $call) {
            $call->lead_id ? LeadAiAutoTrigger::lead('sales', $call->lead_id) : LeadAiAutoTrigger::number($call->caller_id_number);
        });
    }
}
