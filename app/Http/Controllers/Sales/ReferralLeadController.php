<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\DoctorConsultationService;
use App\Models\SalesReferralLead;
use App\Models\Service;
use App\Services\SalesReferralLeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReferralLeadController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $items = SalesReferralLead::query()
            ->with(['assignedExecutive:id,f_name,l_name', 'lead:id,status,stage'])
            ->where('generated_by_user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        $serviceOptions = $this->serviceOptions();
        $referralLeadCommissionPercent = $user->referralLeadCommissionPercent();

        return view('sales.referral_leads.index', compact('items', 'serviceOptions', 'referralLeadCommissionPercent'));
    }

    public function store(Request $request, SalesReferralLeadService $leadService)
    {
        try {
            $leadService->createLead(Auth::user(), $request->all(), SalesReferralLeadService::SOURCE_SALES);
        } catch (ValidationException $e) {
            return redirect()
                ->route('sales.referral_leads.index')
                ->withErrors($e->errors())
                ->withInput();
        }

        return redirect()
            ->route('sales.referral_leads.index')
            ->with('status', [
                'alert_type' => 'success',
                'message' => 'Lead generated and assigned to sales automatically.',
            ]);
    }

    private function serviceOptions(): array
    {
        $regular = Service::query()->select('name')->orderBy('name')->pluck('name')->toArray();
        $doctor = DoctorConsultationService::query()->select('name')->orderBy('name')->pluck('name')->toArray();
        $all = array_values(array_unique(array_filter(array_merge($regular, $doctor))));
        natcasesort($all);

        return array_values($all);
    }
}
