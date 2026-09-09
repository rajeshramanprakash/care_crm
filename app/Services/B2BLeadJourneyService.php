<?php

namespace App\Services;

use App\Models\B2BLead;
use App\Models\Lead;
use App\Models\LeadStatusRemark;
use App\Models\OperationDeploymentDetails;
use App\Models\OperationLead;
use App\Models\ReceivedPayment;
use App\Models\User;

class B2BLeadJourneyService
{
    public function buildDetail(B2BLead $b2bLead): array
    {
        $b2bLead->loadMissing(['lead.statusRemarks', 'lead.executiveUser', 'operationLead']);

        $lead = $b2bLead->lead;
        $operationLead = $this->resolveOperationLead($b2bLead, $lead);

        return [
            'b2b_lead_id' => $b2bLead->id,
            'submitted' => $this->submittedPayload($b2bLead),
            'sales' => $this->salesPayload($lead),
            'status_remarks' => $this->statusRemarksPayload($lead),
            'operation' => $this->operationPayload($operationLead),
            'deployments' => $this->deploymentsPayload($operationLead),
            'payments' => $this->paymentsPayload($operationLead),
            'timeline' => $this->timelinePayload($b2bLead, $lead, $operationLead),
            'updated_at' => now()->format('d M Y, h:i A'),
        ];
    }

    protected function resolveOperationLead(B2BLead $b2bLead, ?Lead $lead): ?OperationLead
    {
        if ($b2bLead->operationLead) {
            return $b2bLead->operationLead;
        }
        if (! $lead) {
            return null;
        }

        $op = OperationLead::query()
            ->where(function ($q) use ($lead) {
                $q->where('lead_id', $lead->formatted_id())
                    ->orWhere('contact_no', $lead->contact_no);
            })
            ->orderByDesc('id')
            ->first();

        if ($op && $b2bLead->operation_lead_id !== $op->id) {
            $b2bLead->operation_lead_id = $op->id;
            $b2bLead->save();
        }

        return $op;
    }

    protected function submittedPayload(B2BLead $b2bLead): array
    {
        $payload = [
            'name' => $b2bLead->name,
            'mobile' => $b2bLead->mobile,
            'service_requirement' => $b2bLead->service_requirement,
            'source' => $b2bLead->source ?? 'manual',
            'submitted_at' => $b2bLead->created_at?->format('d M Y, h:i A') ?? '—',
        ];

        if ($b2bLead->detail !== null) {
            $payload['detail'] = $b2bLead->detail;
        }
        if ($b2bLead->bulk_qty !== null) {
            $payload['bulk_qty'] = $b2bLead->bulk_qty;
        }

        return $payload;
    }

    protected function salesPayload(?Lead $lead): ?array
    {
        if (! $lead) {
            return null;
        }

        return [
            'lead_no' => $lead->formatted_id(),
            'customer_name' => $lead->customer_name,
            'contact_no' => $lead->contact_no,
            'location' => $lead->location,
            'query' => $lead->query,
            'query_remarks' => $lead->query_remarks,
            'status' => $lead->status,
            'stage' => $lead->stage,
            'lead_source' => $lead->lead_source,
            'executive_name' => $lead->executiveUser?->name ?? '—',
            'follow_up_date' => $lead->follow_up_date?->format('d M Y, h:i A'),
            'future_prospect_date' => $lead->future_prospect_date?->format('d M Y, h:i A'),
            'prospect_rate' => $lead->prospect_rate,
            'shift_type' => $lead->shift_type,
            'inactive_stage_remark' => $lead->inactive_stage_remark,
            'status_remarks_legacy' => $lead->status_remarks,
            'last_call_status' => $lead->last_call_status_display ?? $lead->last_call_status,
            'created_at' => $lead->created_at?->format('d M Y, h:i A') ?? '—',
            'updated_at' => $lead->updated_at?->format('d M Y, h:i A') ?? '—',
        ];
    }

    protected function statusRemarksPayload(?Lead $lead): array
    {
        if (! $lead) {
            return [];
        }

        return $lead->statusRemarks()
            ->orderBy('id')
            ->get()
            ->map(fn (LeadStatusRemark $r) => [
                'remark' => $r->remark,
                'original_remark' => $r->original_remark,
                'is_ai_polished' => (bool) $r->is_ai_polished,
                'status_at_remark' => $r->status_at_remark,
                'created_by_name' => $r->created_by_name,
                'created_at' => $r->created_at?->format('d M Y, h:i A') ?? '—',
            ])
            ->values()
            ->all();
    }

    protected function operationPayload(?OperationLead $op): ?array
    {
        if (! $op) {
            return null;
        }

        $executive = $op->executive
            ? User::query()->find($op->executive)
            : null;

        return [
            'operation_lead_id' => $op->id,
            'customer_name' => $op->customer_name,
            'contact_no' => $op->contact_no,
            'location' => $op->location,
            'query' => $op->query,
            'query_remark' => $op->query_remark,
            'status' => $op->status,
            'executive_name' => $executive?->name ?? ($op->executive_name ?? '—'),
            'status_remark' => $op->status_remark,
            'price_issue_remark' => $op->price_issue_remark,
            'inactive_remark' => $op->inactive_remark,
            'closed_remark' => $op->closed_remark,
            'follow_up_date' => $op->follow_up_date?->format('d M Y, h:i A'),
            'future_prospect_date' => $op->future_prospect_date?->format('d M Y, h:i A'),
            'shift_type' => $op->shift_type,
            'closed_rate' => $op->closed_rate,
            'patient_name' => $op->patient_name,
            'forwarded_at' => $op->created_at?->format('d M Y, h:i A') ?? ($op->date_time ?? '—'),
            'updated_at' => $op->updated_at?->format('d M Y, h:i A') ?? '—',
        ];
    }

    protected function deploymentsPayload(?OperationLead $op): array
    {
        if (! $op) {
            return [];
        }

        return OperationDeploymentDetails::query()
            ->with(['vendor', 'freelanceStaff'])
            ->where('operation_lead_id', $op->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($d) => [
                'vendor' => $d->vendor?->name ?? $d->staff_name ?? '—',
                'deployment_status' => $d->deployment_status,
                'from_date' => $d->deployment_from_date?->format('d M Y') ?? '—',
                'to_date' => $d->deployment_to_date?->format('d M Y') ?? '—',
                'vendor_payment' => $d->vendor_payment,
                'created_at' => $d->created_at?->format('d M Y, h:i A') ?? '—',
            ])
            ->values()
            ->all();
    }

    protected function paymentsPayload(?OperationLead $op): array
    {
        if (! $op) {
            return [];
        }

        return ReceivedPayment::query()
            ->where('operation_lead_id', $op->id)
            ->orderByDesc('received_date')
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'amount' => $p->amount,
                'received_date' => $p->received_date?->format('d M Y') ?? '—',
                'remark' => $p->remark,
            ])
            ->values()
            ->all();
    }

    /**
     * Chronological milestones for B2B partner visibility.
     *
     * @return list<array{at: string, title: string, detail: string, kind: string}>
     */
    protected function timelinePayload(B2BLead $b2bLead, ?Lead $lead, ?OperationLead $op): array
    {
        $events = [];

        $events[] = [
            'at' => $b2bLead->created_at?->format('d M Y, h:i A') ?? '—',
            'sort' => $b2bLead->created_at?->timestamp ?? 0,
            'title' => 'Submitted by you (B2B)',
            'detail' => trim($b2bLead->service_requirement . ' — ' . $b2bLead->mobile),
            'kind' => 'b2b',
        ];

        if ($lead) {
            $events[] = [
                'at' => $lead->created_at?->format('d M Y, h:i A') ?? '—',
                'sort' => $lead->created_at?->timestamp ?? 0,
                'title' => 'Assigned to Sales',
                'detail' => 'Status: ' . ($lead->status ?? '—') . ', Stage: ' . ($lead->stage ?? '—'),
                'kind' => 'sales',
            ];

            $remarks = $lead->relationLoaded('statusRemarks')
                ? $lead->statusRemarks
                : $lead->statusRemarks()->orderBy('id')->get();
            foreach ($remarks as $r) {
                $events[] = [
                    'at' => $r->created_at?->format('d M Y, h:i A') ?? '—',
                    'sort' => $r->created_at?->timestamp ?? 0,
                    'title' => 'Sales status update' . ($r->status_at_remark ? ' (' . $r->status_at_remark . ')' : ''),
                    'detail' => $r->remark,
                    'kind' => 'remark',
                ];
            }
        }

        if ($op) {
            $events[] = [
                'at' => $op->created_at?->format('d M Y, h:i A') ?? '—',
                'sort' => $op->created_at?->timestamp ?? 0,
                'title' => 'Forwarded to Operation',
                'detail' => 'Operation status: ' . ($op->status ?? '—'),
                'kind' => 'operation',
            ];
            if ($op->updated_at && $op->created_at && $op->updated_at->gt($op->created_at)) {
                $events[] = [
                    'at' => $op->updated_at->format('d M Y, h:i A'),
                    'sort' => $op->updated_at->timestamp,
                    'title' => 'Operation lead last updated',
                    'detail' => 'Current status: ' . ($op->status ?? '—'),
                    'kind' => 'operation',
                ];
            }
        }

        usort($events, fn ($a, $b) => ($a['sort'] <=> $b['sort']));

        return array_map(fn ($e) => [
            'at' => $e['at'],
            'title' => $e['title'],
            'detail' => $e['detail'],
            'kind' => $e['kind'],
        ], $events);
    }
}
