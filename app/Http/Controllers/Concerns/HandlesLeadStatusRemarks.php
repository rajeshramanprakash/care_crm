<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Lead;
use App\Models\LeadStatusRemark;
use App\Services\LeadStatusRemarkAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

trait HandlesLeadStatusRemarks
{
    protected function statusRemarksStorageReady(): bool
    {
        return Schema::hasTable('lead_status_remarks')
            && Schema::hasColumn('lead_status_remarks', 'lead_id')
            && Schema::hasColumn('lead_status_remarks', 'remark');
    }

    protected function leadStatusRemarksPayload(Lead $lead, bool $adminCanEdit = false): array
    {
        if (! $this->statusRemarksStorageReady()) {
            return [];
        }

        return $lead->statusRemarks()
            ->orderBy('id')
            ->get()
            ->map(fn (LeadStatusRemark $r) => [
                'id' => $r->id,
                'remark' => $r->remark,
                'original_remark' => $r->original_remark,
                'is_ai_polished' => (bool) $r->is_ai_polished,
                'ai_generated_at' => $r->ai_generated_at?->format('d M Y, h:i A'),
                'status_at_remark' => $r->status_at_remark,
                'created_by_name' => $r->created_by_name,
                'created_at' => $r->created_at?->format('d M Y, h:i A') ?? '—',
                'can_edit' => $adminCanEdit,
            ])
            ->values()
            ->all();
    }

    protected function appendLeadStatusRemarksToResponse(Lead $lead, bool $adminCanEdit = false): Lead
    {
        $lead->setAttribute('status_remarks_list', $this->leadStatusRemarksPayload($lead, $adminCanEdit));

        return $lead;
    }

    /**
     * @return JsonResponse|null
     */
    protected function validateSalesManagerNewStatusRemark(Lead $lead, Request $request): ?JsonResponse
    {
        $text = trim((string) $request->input('new_status_remark', ''));
        $original = trim((string) $request->input('new_status_remark_original', ''));

        if ($text === '' && $original === '') {
            return null;
        }

        if (! $this->statusRemarksStorageReady()) {
            return response()->json([
                'success' => false,
                'message' => 'Status remarks are not set up on the server. Please run database migrations (lead_status_remarks) and try again.',
            ], 503);
        }

        $token = (string) $request->input('status_remark_ai_token', '');
        $confirmed = (string) $request->input('status_remark_ai_confirmed', '') === '1';

        if ($token === '' && ! $confirmed) {
            return null;
        }

        /** @var LeadStatusRemarkAiService $ai */
        $ai = app(LeadStatusRemarkAiService::class);

        if ($original === '' || $text === '' || ! $ai->verifyAiToken('lead', (int) $lead->id, $original, $text, $token)) {
            return response()->json([
                'success' => false,
                'message' => 'AI polish could not be verified. Click "Generate with AI" again or save your notes without using AI.',
            ], 422);
        }

        return null;
    }

    protected function processNewStatusRemark(Lead $lead, Request $request, ?string $status = null, bool $requireAi = false): void
    {
        $text = trim((string) $request->input('new_status_remark', ''));
        $original = trim((string) $request->input('new_status_remark_original', ''));

        if ($text === '' && $original !== '') {
            $text = $original;
        }

        if ($text === '') {
            return;
        }

        $token = (string) $request->input('status_remark_ai_token', '');
        /** @var LeadStatusRemarkAiService $ai */
        $ai = app(LeadStatusRemarkAiService::class);
        $isAiPolished = $original !== ''
            && $token !== ''
            && $ai->verifyAiToken('lead', (int) $lead->id, $original, $text, $token);

        if ($requireAi && ! $isAiPolished) {
            return;
        }

        if (! $this->statusRemarksStorageReady()) {
            return;
        }

        $user = auth()->user();
        $originalRemark = $isAiPolished ? $original : null;

        $attrs = [
            'lead_id' => $lead->id,
            'remark' => $text,
            'status_at_remark' => $status ?? $lead->status,
            'created_by' => $user?->id,
            'created_by_name' => $user?->name ?? 'Unknown',
        ];

        if (Schema::hasColumn('lead_status_remarks', 'original_remark')) {
            $attrs['original_remark'] = $isAiPolished ? $originalRemark : null;
        }
        if (Schema::hasColumn('lead_status_remarks', 'is_ai_polished')) {
            $attrs['is_ai_polished'] = $isAiPolished;
        }
        if (Schema::hasColumn('lead_status_remarks', 'ai_generated_at')) {
            $attrs['ai_generated_at'] = $isAiPolished ? now() : null;
        }

        LeadStatusRemark::create($attrs);

        $this->syncLeadLatestStatusRemark($lead);
    }

    protected function syncLeadLatestStatusRemark(Lead $lead): void
    {
        $latest = $lead->statusRemarks()->orderByDesc('id')->value('remark');
        if ($latest !== null) {
            $lead->update(['status_remarks' => $latest]);
        }
    }

    protected function processAdminStatusRemarkUpdates(Lead $lead, Request $request): void
    {
        if (! auth()->user()?->hasRole('Admin')) {
            return;
        }

        $updates = $request->input('remark_updates', []);
        if (! is_array($updates)) {
            return;
        }

        foreach ($updates as $remarkId => $text) {
            $remark = LeadStatusRemark::query()
                ->where('lead_id', $lead->id)
                ->whereKey($remarkId)
                ->first();
            if (! $remark) {
                continue;
            }
            $text = trim((string) $text);
            if ($text === '') {
                continue;
            }
            $remark->update(['remark' => $text]);
        }

        $this->syncLeadLatestStatusRemark($lead->fresh());
    }

    protected function crmLeadStatusRemarksForDisplay(?Lead $crmLead): array
    {
        if (! $crmLead) {
            return [];
        }

        return $this->leadStatusRemarksPayload($crmLead, false);
    }
}
