<?php

namespace App\Http\Controllers\Concerns;

use App\Models\OperationLead;
use App\Models\OperationLeadStatusRemark;
use App\Services\LeadStatusRemarkAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

trait HandlesOperationLeadStatusRemarks
{
    protected function operationStatusRemarksStorageReady(): bool
    {
        return Schema::hasTable('operation_lead_status_remarks')
            && Schema::hasColumn('operation_lead_status_remarks', 'operation_lead_id')
            && Schema::hasColumn('operation_lead_status_remarks', 'remark');
    }

    protected function operationLeadStatusRemarksPayload(OperationLead $lead): array
    {
        if (! $this->operationStatusRemarksStorageReady()) {
            return [];
        }

        return $lead->statusRemarks()
            ->orderBy('id')
            ->get()
            ->map(fn (OperationLeadStatusRemark $r) => [
                'id' => $r->id,
                'remark' => $r->remark,
                'original_remark' => $r->original_remark,
                'is_ai_polished' => (bool) $r->is_ai_polished,
                'ai_generated_at' => $r->ai_generated_at?->format('d M Y, h:i A'),
                'status_at_remark' => $r->status_at_remark,
                'created_by_name' => $r->created_by_name,
                'created_at' => $r->created_at?->format('d M Y, h:i A') ?? '—',
                'can_edit' => false,
            ])
            ->values()
            ->all();
    }

    protected function appendOperationLeadStatusRemarksToResponse(OperationLead $lead): OperationLead
    {
        $lead->setAttribute('status_remarks_list', $this->operationLeadStatusRemarksPayload($lead));

        return $lead;
    }

    protected function validateOperationNewStatusRemark(OperationLead $lead, Request $request): ?JsonResponse
    {
        $text = trim((string) $request->input('new_status_remark', ''));
        if ($text === '') {
            return null;
        }

        if (! $this->operationStatusRemarksStorageReady()) {
            return response()->json([
                'success' => false,
                'message' => 'Status remarks are not set up on the server. Please run database migrations (operation_lead_status_remarks) and try again.',
            ], 503);
        }

        $original = trim((string) $request->input('new_status_remark_original', ''));
        $token = (string) $request->input('status_remark_ai_token', '');

        /** @var LeadStatusRemarkAiService $ai */
        $ai = app(LeadStatusRemarkAiService::class);

        if ($original === '' || ! $ai->verifyAiToken('operation_lead', (int) $lead->id, $original, $text, $token)) {
            return response()->json([
                'success' => false,
                'message' => 'Please use "Generate with AI" on your status remark before saving. The polished English text is required.',
            ], 422);
        }

        return null;
    }

    protected function processOperationNewStatusRemark(OperationLead $lead, Request $request, ?string $status = null): void
    {
        $text = trim((string) $request->input('new_status_remark', ''));
        if ($text === '') {
            return;
        }

        $original = trim((string) $request->input('new_status_remark_original', ''));
        $token = (string) $request->input('status_remark_ai_token', '');

        /** @var LeadStatusRemarkAiService $ai */
        $ai = app(LeadStatusRemarkAiService::class);
        if ($original === '' || ! $ai->verifyAiToken('operation_lead', (int) $lead->id, $original, $text, $token)) {
            return;
        }

        if (! $this->operationStatusRemarksStorageReady()) {
            return;
        }

        $user = auth()->user();
        $isAiPolished = $original !== '';

        $attrs = [
            'operation_lead_id' => $lead->id,
            'remark' => $text,
            'status_at_remark' => $status ?? $lead->status,
            'created_by' => $user?->id,
            'created_by_name' => $user?->name ?? 'Unknown',
        ];

        if (Schema::hasColumn('operation_lead_status_remarks', 'original_remark')) {
            $attrs['original_remark'] = $isAiPolished ? $original : null;
        }
        if (Schema::hasColumn('operation_lead_status_remarks', 'is_ai_polished')) {
            $attrs['is_ai_polished'] = $isAiPolished;
        }
        if (Schema::hasColumn('operation_lead_status_remarks', 'ai_generated_at')) {
            $attrs['ai_generated_at'] = $isAiPolished ? now() : null;
        }

        OperationLeadStatusRemark::create($attrs);

        $this->syncOperationLeadLatestStatusRemark($lead);
    }

    protected function syncOperationLeadLatestStatusRemark(OperationLead $lead): void
    {
        if (! $this->operationStatusRemarksStorageReady()) {
            return;
        }

        $latest = $lead->statusRemarks()->orderByDesc('id')->value('remark');
        if ($latest !== null) {
            $lead->update(['status_remark' => $latest]);
        }
    }
}
