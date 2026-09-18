<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Models\SalesReferralLead;
use App\Models\User;
use Illuminate\Http\Request;

class SubAdminReferralLeadController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_referral_leads');
    }

    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', ''));

        $query = SalesReferralLead::query()
            ->with([
                'generatedBy:id,f_name,l_name',
                'manager:id,f_name,l_name',
                'assignedExecutive:id,f_name,l_name',
                'reviewedBy:id,f_name,l_name',
                'lead:id,status,stage',
            ])
            ->orderByDesc('id');

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $items = $query->paginate(50)->withQueryString();

        $counts = [
            'all' => SalesReferralLead::query()->count(),
            'pending' => SalesReferralLead::query()->where('status', 'pending')->count(),
            'approved' => SalesReferralLead::query()->where('status', 'approved')->count(),
            'rejected' => SalesReferralLead::query()->where('status', 'rejected')->count(),
        ];

        $commissionUsers = User::query()
            ->where(function ($q) {
                $q->whereRaw('FIND_IN_SET(role_id, "2")')
                    ->orWhereRaw('FIND_IN_SET(role_id, "4")');
            })
            ->where('is_active', 1)
            ->orderByRaw("CASE WHEN FIND_IN_SET(role_id, '2') THEN 0 ELSE 1 END")
            ->orderBy('f_name')
            ->orderBy('l_name')
            ->get(['id', 'f_name', 'l_name', 'mobile', 'role_id', 'referral_lead_commission_percent']);

        return view('subadmin.referral_leads.index', compact('items', 'counts', 'status', 'commissionUsers'));
    }

    /**
     * Bulk-set referral-lead commission % for selected Sales / Operation users.
     */
    public function updateCommission(Request $request)
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $percent = round((float) $data['commission_percent'], 2);
        $ids = array_values(array_unique(array_map('intval', $data['user_ids'])));

        $updated = User::query()
            ->whereIn('id', $ids)
            ->where(function ($q) {
                $q->whereRaw('FIND_IN_SET(role_id, "2")')
                    ->orWhereRaw('FIND_IN_SET(role_id, "4")');
            })
            ->update(['referral_lead_commission_percent' => $percent]);

        return redirect()
            ->route('admin.referral_leads.index')
            ->with('status', [
                'alert_type' => 'success',
                'message' => "Referral lead commission set to {$percent}% for {$updated} user(s).",
            ]);
    }
}
