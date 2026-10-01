<?php

namespace App\Http\Controllers\Concerns;

use App\Jobs\ApplyBulkPricingRuleJob;
use App\Models\BulkPricingRule;
use App\Models\DoctorConsultationService;
use App\Models\DoctorConsultationServiceSubService;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceSubService;
use App\Services\BulkPricing\BulkPricingRuleApplier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait HandlesBulkPricingRules
{
    abstract protected function bulkRoutePrefix(): string;

    public function index()
    {
        $activeSubs = fn ($q) => $q->where('is_active', true)->orderBy('name');
        $services = Service::query()->with(['subServices' => $activeSubs])->orderBy('name')->get();
        $doctorServices = DoctorConsultationService::query()
            ->where('is_active', true)
            ->with(['subServices' => $activeSubs])
            ->orderBy('name')
            ->get();

        $rules = BulkPricingRule::query()
            ->with(['service', 'subService', 'doctorService', 'doctorSubService'])
            ->latest('id')
            ->paginate(25);

        $routePrefix = $this->bulkRoutePrefix();

        return view('admin.bulk_registration.index', compact('services', 'doctorServices', 'rules', 'routePrefix'));
    }

    public function store(Request $request)
    {
        $doctorModes = ['online', 'home_visit', 'clinic_visit'];
        $providerModes = ['12_hours', '24_hours', 'both', 'one_time'];
        $isDoctor = in_array($request->input('pricing_type'), ['doctor_payout', 'website_doctor'], true);

        if ($request->input('pricing_type') === 'website_general') {
            $request->merge(['apply_to' => 'all', 'apply_from_date' => null, 'apply_to_date' => null]);
        }
        if ($request->input('apply_to') === 'all') {
            $request->merge(['apply_from_date' => null, 'apply_to_date' => null]);
        }
        if ($request->input('time_period') === 'permanent') {
            $request->merge(['time_period_start_date' => null, 'time_period_end_date' => null]);
        }
        if (! in_array($request->input('city_filter'), ['tier_1', 'tier_2', 'tier_3'], true)) {
            $request->merge(['selected_cities' => null]);
        }

        $validated = $request->validate([
            'pricing_type' => ['required', Rule::in(array_keys(BulkPricingRule::PRICING_TYPE_LABELS))],
            'service_id' => ['nullable', 'integer', $isDoctor ? 'exists:doctor_consultation_services,id' : 'exists:services,id'],
            'sub_service_id' => ['nullable', 'integer'],
            'mode_type' => ['nullable', Rule::in($isDoctor ? $doctorModes : $providerModes)],
            'change_type' => ['required', Rule::in(array_keys(BulkPricingRule::CHANGE_TYPE_LABELS))],
            'value' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'apply_to' => ['required', Rule::in(array_keys(BulkPricingRule::APPLY_TO_LABELS))],
            'apply_from_date' => ['nullable', 'required_if:apply_to,new,old', 'date'],
            'apply_to_date' => ['nullable', 'required_if:apply_to,new,old', 'date', 'after_or_equal:apply_from_date'],
            'city_filter' => ['required', Rule::in(['all', 'tier_1', 'tier_2', 'tier_3'])],
            'selected_cities' => ['nullable', 'array'],
            'selected_cities.*' => ['integer'],
            'time_period' => ['required', Rule::in(['permanent', 'temporary'])],
            'time_period_start_date' => ['nullable', 'required_if:time_period,temporary', 'date'],
            'time_period_end_date' => ['nullable', 'required_if:time_period,temporary', 'date', 'after:time_period_start_date'],
        ], [
            'apply_from_date.required_if' => 'Registration From Date select karein.',
            'apply_to_date.required_if' => 'Registration To Date select karein.',
            'time_period_start_date.required_if' => 'Temporary price ki From Date select karein.',
            'time_period_end_date.required_if' => 'Temporary price ki To Date select karein.',
            'time_period_end_date.after' => 'To Date, From Date ke baad honi chahiye.',
        ]);

        $this->validateBulkRuleConsistency($validated, $isDoctor);

        $selectedCities = array_values(array_unique(array_map('intval', $validated['selected_cities'] ?? [])));
        if ($selectedCities !== []) {
            $tierName = 'Tier '.substr($validated['city_filter'], -1);
            $selectedCities = Location::query()->where('tier', $tierName)->whereIn('id', $selectedCities)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $start = ! empty($validated['time_period_start_date']) ? Carbon::parse($validated['time_period_start_date']) : null;
        $scheduled = $validated['time_period'] === 'temporary' && $start && $start->isFuture();

        $user = $request->user();
        $rule = BulkPricingRule::create([
            'pricing_type' => $validated['pricing_type'],
            'service_id' => $validated['service_id'] ?? null,
            'sub_service_id' => ! empty($validated['service_id']) ? ($validated['sub_service_id'] ?? null) : null,
            'mode_type' => $validated['mode_type'] ?? null,
            'change_type' => $validated['change_type'],
            'value' => $validated['value'],
            'apply_to' => $validated['apply_to'],
            'apply_from_date' => $validated['apply_from_date'] ?? null,
            'apply_to_date' => $validated['apply_to_date'] ?? null,
            'city_filter' => $validated['city_filter'],
            'selected_cities' => $selectedCities !== [] ? $selectedCities : null,
            'time_period' => $validated['time_period'],
            'time_period_start_date' => $validated['time_period_start_date'] ?? null,
            'time_period_end_date' => $validated['time_period_end_date'] ?? null,
            'status' => 1,
            'state' => $scheduled ? BulkPricingRule::STATE_SCHEDULED : BulkPricingRule::STATE_ACTIVE,
            'created_by' => $user?->id,
            'created_by_name' => $user?->name,
        ]);

        if ($scheduled) {
            return redirect()->back()->with('success', "Rule #{$rule->id} schedule ho gaya hai. Price {$start->format('d M Y, h:i A')} se change honge.");
        }

        ApplyBulkPricingRuleJob::dispatch($rule);
        $rule->refresh();

        if ($rule->state === BulkPricingRule::STATE_FAILED) {
            return redirect()->back()->with('error', "Rule #{$rule->id} apply nahi ho paya: {$rule->error_message}");
        }
        if ($rule->applied_at) {
            return redirect()->back()->with('success', "Rule #{$rule->id} apply ho gaya. {$rule->affected_count} price(s) change hue.");
        }

        return redirect()->back()->with('success', "Rule #{$rule->id} save ho gaya, background mein apply ho raha hai.");
    }

    public function changes(BulkPricingRule $bulkPricingRule)
    {
        $changes = $bulkPricingRule->changes()->orderBy('id')->limit(2000)->get();

        return response()->json([
            'rule_id' => $bulkPricingRule->id,
            'total' => $bulkPricingRule->changes()->count(),
            'changes' => $changes->map(fn ($c) => [
                'label' => $c->label,
                'field' => $c->field,
                'old_value' => $c->old_value,
                'new_value' => $c->new_value,
                'changed_at' => $c->created_at?->format('d M Y, h:i A'),
                'reverted_at' => $c->reverted_at?->format('d M Y, h:i A'),
            ]),
        ]);
    }

    public function stop(BulkPricingRule $bulkPricingRule)
    {
        $rule = $bulkPricingRule;
        if (! in_array($rule->state, [BulkPricingRule::STATE_SCHEDULED, BulkPricingRule::STATE_ACTIVE], true)) {
            return redirect()->back()->with('error', "Rule #{$rule->id} already band hai.");
        }

        if ($rule->isTemporary() && $rule->applied_at) {
            $count = (new BulkPricingRuleApplier())->revert($rule);
            $rule->update(['state' => BulkPricingRule::STATE_REVERTED, 'status' => 0, 'reverted_at' => now()]);

            return redirect()->back()->with('success', "Rule #{$rule->id} band karke purane price wapas laga diye ({$count} price restore hue).");
        }

        $rule->update([
            'state' => $rule->applied_at ? BulkPricingRule::STATE_COMPLETED : BulkPricingRule::STATE_REVERTED,
            'status' => 0,
            'reverted_at' => $rule->applied_at ? null : now(),
        ]);

        return redirect()->back()->with('success', "Rule #{$rule->id} band kar diya. Aage koi nayi registration par apply nahi hoga.");
    }

    public function getCitiesByTier(Request $request)
    {
        $tierFilter = $request->query('tier');
        if (in_array($tierFilter, ['tier_1', 'tier_2', 'tier_3'], true)) {
            $tierName = 'Tier '.substr($tierFilter, -1);
            $cities = Location::query()->where('tier', $tierName)->orderBy('name')->get(['id', 'name', 'state']);

            return response()->json(['success' => true, 'cities' => $cities]);
        }

        return response()->json(['success' => false, 'cities' => []]);
    }

    private function validateBulkRuleConsistency(array $validated, bool $isDoctor): void
    {
        $errors = [];

        if (str_ends_with($validated['change_type'], '_percent') && str_starts_with($validated['change_type'], 'decrease') && (float) $validated['value'] > 100) {
            $errors['value'] = 'Percentage decrease 100% se zyada nahi ho sakta.';
        }

        $serviceId = $validated['service_id'] ?? null;
        $subId = $validated['sub_service_id'] ?? null;
        if ($subId && ! $serviceId) {
            $errors['sub_service_id'] = 'Sub service ke liye pehle service select karein.';
        } elseif ($subId) {
            $belongs = $isDoctor
                ? DoctorConsultationServiceSubService::query()->whereKey($subId)->where('doctor_consultation_service_id', $serviceId)->exists()
                : ServiceSubService::query()->whereKey($subId)->where('service_id', $serviceId)->exists();
            if (! $belongs) {
                $errors['sub_service_id'] = 'Selected sub service is service ki nahi hai.';
            }
        }

        if ($validated['apply_to'] === 'old' && ! empty($validated['apply_from_date']) && Carbon::parse($validated['apply_from_date'])->isFuture()) {
            $errors['apply_from_date'] = 'Old registrations ke liye From Date aaj ya pehle ki honi chahiye.';
        }

        if ($validated['time_period'] === 'temporary' && ! empty($validated['time_period_end_date']) && Carbon::parse($validated['time_period_end_date'])->isPast()) {
            $errors['time_period_end_date'] = 'Temporary price ki To Date future mein honi chahiye.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
