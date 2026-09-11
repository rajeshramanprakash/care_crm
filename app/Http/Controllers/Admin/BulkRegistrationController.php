<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class BulkRegistrationController extends Controller
{
    public function index()
    {
        $services = Service::with('subServices')->get();
        $doctorServices = \App\Models\DoctorConsultationService::with('subServices')->get();
        // Also fetch active bulk rules to display
        $activeRules = \App\Models\BulkPricingRule::with(['service', 'subService', 'doctorService', 'doctorSubService'])->where('status', 1)->get();
        return view('admin.bulk_registration.index', compact('services', 'doctorServices', 'activeRules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pricing_type' => 'required|in:doctor_payout,vendor,freelancer,website_general,website_doctor',
            'service_id' => 'nullable|integer',
            'sub_service_id' => 'nullable|integer',
            'mode_type' => 'nullable|string',
            'change_type' => 'required|string',
            'value' => 'required|numeric',
            'apply_to' => 'required|string',
            'apply_from_date' => 'nullable|date',
            'apply_to_date' => 'nullable|date',
            'city_filter' => 'nullable|string',
            'time_period' => 'required|string',
            'time_period_start_date' => 'nullable|date',
            'time_period_end_date' => 'nullable|date',
        ]);

        $rule = \App\Models\BulkPricingRule::create([
            'pricing_type' => $validated['pricing_type'],
            'service_id' => $validated['service_id'] ?? null,
            'sub_service_id' => $validated['sub_service_id'] ?? null,
            'mode_type' => $validated['mode_type'] ?? null,
            'change_type' => $validated['change_type'],
            'value' => $validated['value'],
            'apply_to' => $validated['apply_to'],
            'apply_from_date' => $validated['apply_from_date'] ?? null,
            'apply_to_date' => $validated['apply_to_date'] ?? null,
            'city_filter' => $validated['city_filter'] ?? 'current',
            'time_period' => $validated['time_period'],
            'time_period_start_date' => $validated['time_period_start_date'] ?? null,
            'time_period_end_date' => $validated['time_period_end_date'] ?? null,
            'status' => 1,
        ]);

        if (in_array($rule->apply_to, ['old', 'all'])) {
            \App\Jobs\ApplyBulkPricingRuleJob::dispatch($rule);
        }

        return redirect()->back()->with('success', 'Bulk pricing rule has been created successfully!');
    }
}
