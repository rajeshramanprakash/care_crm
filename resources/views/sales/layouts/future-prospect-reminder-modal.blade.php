{{-- Shown on every Sales layout page for the lead assignee only (Reverb private channel App.Models.User.{executive_id}) --}}
<div class="modal fade future-prospect-reminder-modal" id="futureProspectReminderModal" tabindex="-1" role="dialog" aria-labelledby="futureProspectReminderTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable fp-reminder-dialog" role="document">
        <div class="modal-content fp-reminder-sheet border-0 shadow-lg overflow-hidden">
            <div class="fp-reminder-header position-relative text-white px-4 pt-4 pb-5">
                <div class="d-flex align-items-start gap-3">
                    <div class="fp-reminder-header-icon flex-shrink-0 d-flex align-items-center justify-content-center rounded-3">
                        <i class="fas fa-clock" aria-hidden="true"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h5 class="modal-title fw-semibold mb-1 lh-sm" id="futureProspectReminderTitle">
                            Future contact — callback due
                        </h5>
                        <p class="mb-0 small fp-reminder-header-sub opacity-90" id="futureProspectReminderSubtitle">
                            This lead is assigned to you. The scheduled contact time has been reached.
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-body fp-reminder-body px-4 pt-0 pb-4">
                <div class="fp-reminder-due-card rounded-3 px-3 py-3 mb-3 shadow-sm">
                    <div class="d-flex flex-column flex-sm-row align-items-start align-sm-items-center justify-content-between gap-2">
                        <span class="fp-reminder-due-label text-uppercase fw-semibold">Scheduled for</span>
                        <span class="fp-reminder-due-value fw-semibold text-sm-end" id="fpReminderWhen">-</span>
                    </div>
                </div>

                <div class="fp-reminder-section-head d-flex align-items-center gap-2 mb-3">
                    <span class="fp-reminder-section-line flex-grow-1"></span>
                    <span class="fp-reminder-section-title small fw-semibold text-uppercase">Lead details</span>
                    <span class="fp-reminder-section-line flex-grow-1"></span>
                </div>

                {{-- Strict 2-column grid: equal widths, no Bootstrap col-4 breakage --}}
                <div class="fp-reminder-details-panel rounded-3 p-3 p-md-3">
                    <div class="fp-reminder-grid">
                        <div class="fp-reminder-field rounded-3">
                            <div class="fp-reminder-label">Lead no</div>
                            <div class="fp-reminder-value fp-reminder-value--mono" id="fpReminderLeadCode">-</div>
                        </div>
                        <div class="fp-reminder-field fp-reminder-field--accent rounded-3">
                            <div class="fp-reminder-label">Contact no</div>
                            <div class="fp-reminder-value" id="fpReminderContact">-</div>
                        </div>
                        <div class="fp-reminder-field rounded-3">
                            <div class="fp-reminder-label">Customer name</div>
                            <div class="fp-reminder-value" id="fpReminderCustomer">-</div>
                        </div>
                        <div class="fp-reminder-field rounded-3">
                            <div class="fp-reminder-label">Patient name</div>
                            <div class="fp-reminder-value" id="fpReminderPatient">-</div>
                        </div>
                        <div class="fp-reminder-field rounded-3">
                            <div class="fp-reminder-label">Gender</div>
                            <div class="fp-reminder-value" id="fpReminderGender">-</div>
                        </div>
                        <div class="fp-reminder-field rounded-3">
                            <div class="fp-reminder-label">Age</div>
                            <div class="fp-reminder-value" id="fpReminderAge">-</div>
                        </div>
                        <div class="fp-reminder-field rounded-3">
                            <div class="fp-reminder-label">Location</div>
                            <div class="fp-reminder-value" id="fpReminderLocation">-</div>
                        </div>
                        <div class="fp-reminder-field rounded-3">
                            <div class="fp-reminder-label">Lead source</div>
                            <div class="fp-reminder-value" id="fpReminderLeadSource">-</div>
                        </div>
                        <div class="fp-reminder-field rounded-3">
                            <div class="fp-reminder-label">Query</div>
                            <div class="fp-reminder-value" id="fpReminderQuery">-</div>
                        </div>
                        <div class="fp-reminder-field rounded-3">
                            <div class="fp-reminder-label">Stage</div>
                            <div class="fp-reminder-value" id="fpReminderStage">-</div>
                        </div>
                        <div class="fp-reminder-field fp-reminder-field--full fp-reminder-field--status rounded-3">
                            <div class="fp-reminder-label">Status</div>
                            <div class="fp-reminder-value fp-reminder-status-pill" id="fpReminderStatus">-</div>
                        </div>
                    </div>

                    <div class="fp-reminder-remarks rounded-3 p-3 mt-3 d-none" id="fpReminderQueryRemarksRow">
                        <div class="fp-reminder-label mb-2">Query remarks</div>
                        <div class="fp-reminder-remarks-text text-break" id="fpReminderQueryRemarks">-</div>
                    </div>
                </div>

                <div class="fp-reminder-status mt-3 px-1" id="fpReminderCallStatus" role="status" aria-live="polite"></div>
            </div>

            <div class="modal-footer fp-reminder-footer border-0 px-4 py-3 flex-wrap gap-2 d-flex align-items-center w-100">
                <button type="button" class="btn fp-reminder-btn-close btn-outline-secondary" id="fpReminderCloseBtn">
                    Close
                </button>
                <div class="d-flex flex-wrap gap-2 ms-auto">
                    <button type="button" class="btn fp-reminder-btn-done d-none" id="fpReminderDoneBtn" disabled>
                        <i class="fas fa-check me-2" aria-hidden="true"></i>Done
                    </button>
                    <button type="button" class="btn fp-reminder-btn-callback text-white shadow-sm" id="fpReminderCallBtn">
                        <i class="fas fa-phone-alt me-2" aria-hidden="true"></i><span id="fpReminderCallBtnLabel">Callback</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .future-prospect-reminder-modal.modal {
        z-index: 10050 !important;
    }

    .fp-reminder-dialog {
        max-width: 720px;
    }

    .fp-reminder-sheet {
        border-radius: 16px;
        font-family: inherit;
    }

    .fp-reminder-header {
        background: linear-gradient(145deg, #b45309 0%, #e87722 42%, #f59e3b 100%);
    }

    .fp-reminder-header::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 28px;
        background: linear-gradient(to top, rgba(255, 255, 255, 0.12), transparent);
        pointer-events: none;
    }

    .fp-reminder-header-icon {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.22);
        border: 1px solid rgba(255, 255, 255, 0.4);
        font-size: 1.2rem;
    }

    .fp-reminder-header-sub {
        line-height: 1.45;
    }

    .fp-reminder-body {
        margin-top: -1.25rem;
        position: relative;
        z-index: 1;
        background: #f0f2f5;
        border-radius: 16px 16px 0 0;
    }

    .fp-reminder-due-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .fp-reminder-due-label {
        color: #64748b;
        letter-spacing: 0.08em;
        font-size: 0.7rem;
    }

    .fp-reminder-due-value {
        color: #c2410c;
        font-size: 1.0625rem;
        letter-spacing: 0.02em;
    }

    .fp-reminder-section-line {
        height: 1px;
        background: linear-gradient(90deg, transparent, #cbd5e1 15%, #cbd5e1 85%, transparent);
    }

    .fp-reminder-section-title {
        color: #64748b;
        letter-spacing: 0.1em;
        font-size: 0.7rem;
        white-space: nowrap;
    }

    /* Panel behind all boxes — clear separation from page */
    .fp-reminder-details-panel {
        background: #e8ecf1;
        border: 1px solid #d8dee6;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
    }

    .fp-reminder-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 10px 12px;
        align-items: stretch;
    }

    @media (max-width: 520px) {
        .fp-reminder-grid {
            grid-template-columns: 1fr;
        }
        .fp-reminder-field--full {
            grid-column: 1 !important;
        }
    }

    .fp-reminder-field {
        background: #fff;
        border: 1px solid #dce3ec;
        padding: 12px 14px;
        min-height: 76px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .fp-reminder-field:hover {
        border-color: #c5cdd8;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
    }

    .fp-reminder-field--accent {
        border-color: rgba(232, 119, 34, 0.45);
        background: linear-gradient(180deg, #fffbf7 0%, #fff 55%);
        box-shadow: 0 1px 3px rgba(232, 119, 34, 0.08);
    }

    .fp-reminder-field--full {
        grid-column: 1 / -1;
    }

    .fp-reminder-field--status {
        min-height: auto;
        padding: 12px 14px;
        background: #fff;
        border-color: #e2e8f0;
    }

    .fp-reminder-label {
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        margin-bottom: 6px;
        line-height: 1.2;
    }

    .fp-reminder-value {
        font-size: 0.9375rem;
        font-weight: 600;
        color: #0f172a;
        line-height: 1.4;
        word-break: break-word;
    }

    .fp-reminder-value--mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.9rem;
        letter-spacing: 0.02em;
        color: #1e293b;
    }

    .fp-reminder-status-pill {
        display: inline-block;
        align-self: flex-start;
        margin-top: 2px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.8125rem;
        font-weight: 600;
        background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
        color: #9a3412;
        border: 1px solid #fdba74;
    }

    .fp-reminder-remarks {
        background: #fff;
        border: 1px dashed #94a3b8;
    }

    .fp-reminder-remarks-text {
        font-size: 0.875rem;
        color: #334155;
        line-height: 1.55;
    }

    .fp-reminder-status {
        font-size: 0.8125rem;
        color: #475569;
        font-weight: 500;
        min-height: 1.25rem;
    }

    .fp-reminder-footer {
        background: #fff;
        border-top: 1px solid #e5e7eb !important;
    }

    .fp-reminder-btn-callback {
        --fp-callback: #e87722;
        --fp-callback-hover: #c2410c;
        background: linear-gradient(180deg, #fb923c 0%, var(--fp-callback) 100%);
        border: none;
        font-weight: 600;
        padding: 0.65rem 1.5rem;
        border-radius: 10px;
        letter-spacing: 0.02em;
    }

    .fp-reminder-btn-callback:hover {
        background: linear-gradient(180deg, #fdba74 0%, var(--fp-callback-hover) 100%);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(232, 119, 34, 0.35) !important;
    }

    .fp-reminder-btn-callback:active {
        transform: translateY(0);
    }

    .fp-reminder-btn-callback:disabled {
        opacity: 0.65;
        transform: none;
    }

    .fp-reminder-btn-done {
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #059669;
        font-weight: 600;
        padding: 0.65rem 1.35rem;
        border-radius: 10px;
    }

    .fp-reminder-btn-done:hover:not(:disabled) {
        background: #ecfdf5;
        border-color: #6ee7b7;
        color: #047857;
    }

    .fp-reminder-btn-close {
        font-weight: 600;
        padding: 0.65rem 1.25rem;
        border-radius: 10px;
        border-color: #cbd5e1;
        color: #475569;
    }

    .fp-reminder-btn-close:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #334155;
    }
</style>
