@php
    /** @var \App\Models\DoctorRequest $doctor */
    /** @var \App\Models\DoctorLeegalitySignature|null $leegalitySignature */
    $sig = $leegalitySignature ?? null;
@endphp
<div class="dr-leegality-panel" id="drLeegalityPanel" data-doctor-id="{{ $doctor->id }}">
    <div class="dr-leegality-head">
        <div>
            <div class="dr-leegality-title"><i class="fas fa-file-signature mr-1"></i> Doctor agreement (Leegality)</div>
            <div class="dr-leegality-sub">Send agreement to doctor email for eSign. Status updates via webhook.</div>
        </div>
        <div class="dr-leegality-head-actions">
            <a class="btn btn-sm btn-outline-primary"
               href="{{ route('subadmin.doctor_requests.leegality_preview_agreement', $doctor) }}"
               target="_blank" rel="noopener">
                <i class="fas fa-file-pdf mr-1"></i> View Service Agreement PDF
            </a>
            @if(!$sig || in_array($sig->signature_status, ['REJECTED', 'FAILED', 'SENT'], true))
                <button type="button"
                        class="btn btn-sm btn-warning dr-leegality-send-btn"
                        data-url="{{ route('subadmin.doctor_requests.leegality_send', $doctor) }}">
                    <i class="fas fa-paper-plane mr-1"></i>
                    {{ $sig && $sig->signature_status === 'SENT' ? 'Resend for Signature' : 'Send for Signature' }}
                </button>
            @endif
        </div>
    </div>

    @if($sig)
        <div class="dr-leegality-status-card status-{{ strtolower($sig->signature_status) }}">
            <div class="dr-leegality-status-row">
                <span class="dr-leegality-status-label">Agreement Status</span>
                <span class="dr-leegality-status-value">{{ $sig->statusEmoji() }} {{ $sig->statusLabel() }}</span>
            </div>
            <div class="dr-leegality-meta-grid">
                <div>
                    <div class="dr-leegality-meta-label">Sent</div>
                    <div class="dr-leegality-meta-value">{{ $sig->sent_at ? $sig->sent_at->format('d M Y') : '—' }}</div>
                </div>
                <div>
                    <div class="dr-leegality-meta-label">Signed</div>
                    <div class="dr-leegality-meta-value">{{ $sig->signed_at ? $sig->signed_at->format('d M Y') : '—' }}</div>
                </div>
                <div>
                    <div class="dr-leegality-meta-label">Document ID</div>
                    <div class="dr-leegality-meta-value text-monospace">{{ $sig->leegality_document_id ?: '—' }}</div>
                </div>
                <div>
                    <div class="dr-leegality-meta-label">Doctor email</div>
                    <div class="dr-leegality-meta-value">{{ $sig->signer_email ?: ($doctor->email ?: '—') }}</div>
                </div>
            </div>

            @if($sig->signer_action)
                <div class="dr-leegality-action-note">
                    Signer action: <strong>{{ $sig->signer_action }}</strong>
                    @if($sig->document_status)
                        &nbsp;·&nbsp; Document: <strong>{{ $sig->document_status }}</strong>
                    @endif
                </div>
            @endif

            @if($sig->error_message)
                <div class="dr-leegality-error">{{ $sig->error_message }}</div>
            @endif

            <div class="dr-leegality-actions">
                @if($sig->signed_document)
                    <a class="btn btn-sm btn-outline-success"
                       href="{{ route('subadmin.doctor_requests.leegality_view_signed', [$doctor, $sig]) }}"
                       target="_blank" rel="noopener">
                        <i class="fas fa-eye mr-1"></i> View Signed Agreement
                    </a>
                    <a class="btn btn-sm btn-success"
                       href="{{ route('subadmin.doctor_requests.leegality_download_signed', [$doctor, $sig]) }}">
                        <i class="fas fa-download mr-1"></i> Download Signed PDF
                    </a>
                @endif
                @if($sig->audit_trail)
                    <a class="btn btn-sm btn-outline-primary"
                       href="{{ route('subadmin.doctor_requests.leegality_download_audit', [$doctor, $sig]) }}">
                        <i class="fas fa-clipboard-list mr-1"></i> Download Audit Trail
                    </a>
                @endif
                @if($sig->isSignedLike() || strcasecmp((string) $sig->document_status, 'Completed') === 0)
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary dr-leegality-refresh-btn"
                            data-url="{{ route('subadmin.doctor_requests.leegality_refresh', $doctor) }}">
                        <i class="fas fa-sync-alt mr-1"></i> Refresh files
                    </button>
                @endif
                @if($sig->isSignedLike())
                    <button type="button"
                            class="btn btn-sm btn-primary dr-leegality-add-sig-btn"
                            data-url="{{ route('subadmin.doctor_requests.leegality_add_my_signature', $doctor) }}">
                        <i class="fas fa-file-signature mr-1"></i>
                        {{ $sig->signature_status === 'COMPLETED' ? 'Re-add My Signature' : 'Add My Signature' }}
                    </button>
                @endif
                @if($sig->sign_url && $sig->signature_status === 'SENT')
                    <a class="btn btn-sm btn-outline-warning" href="{{ $sig->sign_url }}" target="_blank" rel="noopener">
                        <i class="fas fa-external-link-alt mr-1"></i> Open sign link
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="dr-leegality-empty">
            No agreement sent yet. Click <strong>Send for Signature</strong> to email the doctor via Leegality.
            @if(!trim((string) $doctor->email))
                <div class="text-danger mt-2"><i class="fas fa-exclamation-circle mr-1"></i> Doctor email is missing on this registration.</div>
            @endif
        </div>
    @endif
</div>
