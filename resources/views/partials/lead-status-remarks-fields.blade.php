@include('includes.lead-status-remarks-assets', ['part' => 'css'])
@php
    $fieldPrefix = $fieldPrefix ?? 'edit';
    $adminCanEditRemarks = $adminCanEditRemarks ?? false;
    $showAiAssist = $showAiAssist ?? (!$adminCanEditRemarks);
    $requireAi = $requireAi ?? false;
    $aiGenerateUrlTemplate = $aiGenerateUrlTemplate ?? null;
@endphp
<div class="form-group mb-3 p-3 bg-light rounded" id="{{ $fieldPrefix }}_status_remarks_group" style="display: none;"
    data-require-ai="{{ $requireAi ? '1' : '0' }}"
    data-ai-url-template="{{ $aiGenerateUrlTemplate ?? '' }}"
    data-field-prefix="{{ $fieldPrefix }}">
    <label class="form-label fw-semibold mb-2">Status Remarks</label>
    <div id="{{ $fieldPrefix }}_status_remarks_history" class="lead-status-remarks-history mb-3"></div>

    @if($showAiAssist)
        <label for="{{ $fieldPrefix }}_new_status_remark_draft" class="form-label small mb-1">Your notes (draft)</label>
        <textarea class="form-control mb-2" id="{{ $fieldPrefix }}_new_status_remark_draft" rows="3"></textarea>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="{{ $fieldPrefix }}_generate_status_remark_ai">
                <i class="fas fa-magic"></i> Generate with AI
            </button>
            <span class="small text-muted" id="{{ $fieldPrefix }}_ai_status_hint">Optional — polish your notes into English before saving.</span>
        </div>
        <label for="{{ $fieldPrefix }}_new_status_remark_preview" class="form-label small mb-1">English translation</label>
        <textarea class="form-control bg-white" id="{{ $fieldPrefix }}_new_status_remark_preview" rows="3" readonly></textarea>
        <input type="hidden" id="{{ $fieldPrefix }}_new_status_remark" name="new_status_remark" value="">
        <input type="hidden" id="{{ $fieldPrefix }}_new_status_remark_original" name="new_status_remark_original" value="">
        <input type="hidden" id="{{ $fieldPrefix }}_status_remark_ai_token" name="status_remark_ai_token" value="">
        <input type="hidden" id="{{ $fieldPrefix }}_status_remark_ai_confirmed" name="status_remark_ai_confirmed" value="0">
    @else
        <label for="{{ $fieldPrefix }}_new_status_remark" class="form-label small mb-1">Add new remark</label>
        <textarea class="form-control" id="{{ $fieldPrefix }}_new_status_remark" name="new_status_remark" rows="3"
            placeholder="Type a new status remark"></textarea>
    @endif

    @if($adminCanEditRemarks)
        <small class="text-muted d-block mt-1">As admin you can edit existing remarks above and save each one.</small>
    @endif
</div>
@include('includes.lead-status-remarks-assets', ['part' => 'js'])
