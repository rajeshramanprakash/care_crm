<?php

namespace App\Http\Controllers\OperationManager;

use App\Http\Controllers\Controller;
use App\Models\SalesReferralLead;
use App\Models\User;
use App\Services\SalesReferralLeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReferralLeadController extends Controller
{
    public function index()
    {
        $manager = Auth::user();
        $teamIds = User::query()
            ->where('parent_id', $manager->id)
            ->pluck('id');

        $items = SalesReferralLead::query()
            ->with([
                'generatedBy:id,f_name,l_name',
                'assignedExecutive:id,f_name,l_name',
                'lead:id,status,stage',
            ])
            ->where(function ($q) use ($manager, $teamIds) {
                $q->where('manager_user_id', $manager->id)
                    ->orWhereIn('generated_by_user_id', $teamIds);
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'approved' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->get();

        return view('operation_manager.referral_leads.index', compact('items'));
    }

    public function approve(SalesReferralLead $referralLead, SalesReferralLeadService $leadService)
    {
        try {
            $leadService->approve($referralLead, Auth::user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('operation-manager.referral_leads.index')
                ->with('status', [
                    'alert_type' => 'error',
                    'message' => collect($e->errors())->flatten()->first() ?: 'Could not approve lead.',
                ]);
        }

        return redirect()
            ->route('operation-manager.referral_leads.index')
            ->with('status', [
                'alert_type' => 'success',
                'message' => 'Referral lead approved and assigned to a sales executive.',
            ]);
    }

    public function reject(Request $request, SalesReferralLead $referralLead, SalesReferralLeadService $leadService)
    {
        $request->validate([
            'manager_remark' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $leadService->reject($referralLead, Auth::user(), $request->input('manager_remark'));
        } catch (ValidationException $e) {
            return redirect()
                ->route('operation-manager.referral_leads.index')
                ->with('status', [
                    'alert_type' => 'error',
                    'message' => collect($e->errors())->flatten()->first() ?: 'Could not reject lead.',
                ]);
        }

        return redirect()
            ->route('operation-manager.referral_leads.index')
            ->with('status', [
                'alert_type' => 'success',
                'message' => 'Referral lead rejected.',
            ]);
    }
}
