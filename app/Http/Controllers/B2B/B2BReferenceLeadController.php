<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Models\B2BLead;
use App\Models\B2BReferenceUser;
use App\Models\B2BUser;
use App\Models\Lead;
use App\Models\OperationDeploymentDetails;
use App\Models\OperationLead;

class B2BReferenceLeadController extends Controller
{
    public function dashboard()
    {
        $refUser = B2BReferenceUser::find(session('b2b_reference_user_id'));
        if (! $refUser || ! $refUser->is_active) {
            return redirect()->route('home')->with('error', 'Session expired. Please login again.');
        }

        $b2bIds = $this->referredB2bUserIds($refUser->id);
        $totalLeads = B2BLead::whereIn('b2b_user_id', $b2bIds)->count();
        $todayLeads = B2BLead::whereIn('b2b_user_id', $b2bIds)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return view('b2b_reference.dashboard', compact('refUser', 'totalLeads', 'todayLeads'));
    }

    public function leads()
    {
        $refUser = B2BReferenceUser::find(session('b2b_reference_user_id'));
        if (! $refUser || ! $refUser->is_active) {
            return redirect()->route('home')->with('error', 'Session expired. Please login again.');
        }

        $b2bIds = $this->referredB2bUserIds($refUser->id);
        $items = B2BLead::with(['operationLead', 'lead', 'b2bUser'])
            ->whereIn('b2b_user_id', $b2bIds)
            ->latest()
            ->get();

        $operationLeadIds = $items->pluck('operation_lead_id')->filter()->unique()->values();
        $latestDeployments = OperationDeploymentDetails::query()
            ->whereIn('operation_lead_id', $operationLeadIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('operation_lead_id')
            ->map(function ($rows) {
                return $rows->first();
            });

        $tableRows = $items->map(function (B2BLead $it) use ($latestDeployments) {
            $b2bUser = $it->b2bUser;
            $lead = $it->lead;
            $op = $it->operationLead;
            if (! $op && $lead) {
                $op = OperationLead::query()
                    ->where(function ($q) use ($lead) {
                        $q->where('lead_id', $lead->formatted_id())
                            ->orWhere('contact_no', $lead->contact_no);
                    })
                    ->orderByDesc('id')
                    ->first();
                if ($op && $it->operation_lead_id !== $op->id) {
                    $it->operation_lead_id = $op->id;
                    $it->save();
                }
            }
            $deployment = $latestDeployments->get($it->operation_lead_id);
            $status = (string) ($lead?->status ?? $op?->status ?? '—');
            $remark = '';
            if (strtolower($status) === 'inactive') {
                $remark = (string) ($lead?->inactive_stage_remark ?: $op?->inactive_remark ?: $op?->status_remark ?: '');
            } elseif (strtolower($status) === 'closed') {
                $remark = (string) ($op?->closed_remark ?: $lead?->status_remarks ?: $op?->status_remark ?: '');
            } else {
                $remark = (string) ($lead?->status_remarks ?: $op?->status_remark ?: '');
            }
            $monthlyAmount = null;
            if (! empty($op?->closed_rate)) {
                $monthlyAmount = (float) $op->closed_rate;
            } elseif (! empty($deployment?->vendor_payment)) {
                $monthlyAmount = (float) $deployment->vendor_payment;
            }
            $commissionPercent = (float) ($b2bUser?->commission_percent ?? 0);
            $monthlyEarning = $monthlyAmount !== null ? round(($monthlyAmount * $commissionPercent) / 100, 2) : null;

            return [
                'lead_no' => (string) (($lead?->formatted_id()) ?? ($op?->lead_id) ?? ('B2B-' . $it->id)),
                'client_name' => (string) ($lead?->customer_name ?? $op->customer_name ?? $it->name),
                'service' => (string) ($lead?->query ?? $op->query ?? $it->service_requirement),
                'city' => (string) ($lead?->location ?? $op->location ?? '—'),
                'price_date' => ($monthlyAmount !== null ? number_format($monthlyAmount, 2) : '—') . ' / ' . (optional($lead?->created_at ?? $op?->date_time ?? $it->created_at)->format('d M Y') ?? '—'),
                'status_with_remark' => trim($status . ($remark !== '' ? (' (' . $remark . ')') : '')),
                'from_to' => strtolower($status) === 'closed'
                    ? ((optional($deployment?->deployment_from_date)->format('d M Y') ?? '—') . ' - ' . (optional($deployment?->deployment_to_date)->format('d M Y') ?? '—'))
                    : '—',
                'total_amount_monthly' => $monthlyAmount !== null ? number_format($monthlyAmount, 2) : '—',
                'total_earnings_monthly' => $monthlyEarning !== null ? number_format($monthlyEarning, 2) : '—',
                'source' => (string) ($lead?->lead_source ?? $it->source ?? 'manual'),
                'b2b_partner' => (string) ($b2bUser?->company_name ?: $b2bUser?->name ?: '—'),
            ];
        });

        return view('b2b_reference.leads', compact('refUser', 'tableRows'));
    }

    /** @return array<int, int> */
    private function referredB2bUserIds(int $referenceUserId): array
    {
        return B2BUser::query()
            ->where('b2b_reference_user_id', $referenceUserId)
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }
}
