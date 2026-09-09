@extends('doctor_carelix.layouts.app')
@section('title', 'Bank Account Details')

@section('main')
@php
use Illuminate\Support\Facades\Storage;
@endphp
<div class="content-wrapper dr-bank-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-12">
                    <div class="card dr-bank-card">
                        <div class="card-header dr-bank-header">
                            <h3 class="card-title mb-1">Bank Account Details</h3>
                            <p class="mb-0">Keep your payout details up to date for smooth settlement processing.</p>
                        </div>
                        <div class="card-body dr-bank-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="bank-field-card">
                                        <label class="bank-label">Account Name</label>
                                        @if($doctor->account_name)
                                            <div class="bank-value">{{ $doctor->account_name }}</div>
                                        @else
                                            <div class="d-flex align-items-center bank-input-wrap">
                                                <input type="text" class="form-control" id="account_name_input" placeholder="Account Name">
                                                <button type="button" class="btn btn-sm bank-btn bank-btn-save" onclick="saveBankField('account_name', 'account_name_input')">
                                                    <i class="fas fa-save mr-1"></i>Save
                                                </button>
                                            </div>
                                            <small class="text-muted">This field needs to be filled</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="bank-field-card">
                                        <label class="bank-label">Account Number</label>
                                        @if($doctor->account_number)
                                            <div class="bank-value">{{ $doctor->account_number }}</div>
                                        @else
                                            <div class="d-flex align-items-center bank-input-wrap">
                                                <input type="text" class="form-control" id="account_number_input" placeholder="Account Number">
                                                <button type="button" class="btn btn-sm bank-btn bank-btn-save" onclick="saveBankField('account_number', 'account_number_input')">
                                                    <i class="fas fa-save mr-1"></i>Save
                                                </button>
                                            </div>
                                            <small class="text-muted">This field needs to be filled</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="bank-field-card">
                                        <label class="bank-label">IFSC Code</label>
                                        @if($doctor->ifsc_code)
                                            <div class="bank-value">{{ $doctor->ifsc_code }}</div>
                                        @else
                                            <div class="d-flex align-items-center bank-input-wrap">
                                                <input type="text" class="form-control" id="ifsc_code_input" placeholder="IFSC Code">
                                                <button type="button" class="btn btn-sm bank-btn bank-btn-save" onclick="saveBankField('ifsc_code', 'ifsc_code_input')">
                                                    <i class="fas fa-save mr-1"></i>Save
                                                </button>
                                            </div>
                                            <small class="text-muted">This field needs to be filled</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="bank-field-card">
                                        <label class="bank-label">UPI ID</label>
                                        @if($doctor->upi_id)
                                            <div class="bank-value">{{ $doctor->upi_id }}</div>
                                        @else
                                            <div class="d-flex align-items-center bank-input-wrap">
                                                <input type="text" class="form-control" id="upi_id_input" placeholder="UPI ID (Optional)">
                                                <button type="button" class="btn btn-sm bank-btn bank-btn-save" onclick="saveBankField('upi_id', 'upi_id_input')">
                                                    <i class="fas fa-save mr-1"></i>Save
                                                </button>
                                            </div>
                                            <small class="text-muted">This field is optional</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="bank-field-card">
                                        <label class="bank-label">Bank Document</label>
                                        <div id="bankDocumentCell">
                                            @if($doctor->bank_document)
                                                <button type="button" class="btn btn-sm bank-btn bank-btn-view" onclick="viewDocument('{{ asset('storage/' . $doctor->bank_document) }}', 'Bank Document')">
                                                    <i class="fas fa-eye mr-1"></i>View
                                                </button>
                                                <a href="{{ asset('storage/' . $doctor->bank_document) }}" download class="btn btn-sm bank-btn bank-btn-download">
                                                    <i class="fas fa-download mr-1"></i>Download
                                                </a>
                                            @else
                                                <input type="file" id="bankDocumentInput" accept="image/*,.pdf" style="display: none;" onchange="uploadBankDocument(this)">
                                                <button type="button" class="btn btn-sm bank-btn bank-btn-upload" onclick="document.getElementById('bankDocumentInput').click()">
                                                    <i class="fas fa-upload mr-1"></i>Upload Bank Document
                                                </button>
                                                <span class="text-muted ml-2">Not uploaded</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Document View Modal -->
<div class="modal fade" id="documentViewModal" tabindex="-1" aria-labelledby="documentViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentViewModalLabel">View Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="documentViewContent">
                    <!-- Document will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function viewDocument(url, title) {
    const modal = new bootstrap.Modal(document.getElementById('documentViewModal'));
    const modalTitle = document.getElementById('documentViewModalLabel');
    const modalContent = document.getElementById('documentViewContent');
    
    modalTitle.textContent = title;
    
    // Check file extension
    const extension = url.split('.').pop().toLowerCase();
    
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
        // Image file
        modalContent.innerHTML = `<img src="${url}" class="img-fluid" alt="${title}" style="max-height: 70vh;">`;
    } else if (extension === 'pdf') {
        // PDF file
        modalContent.innerHTML = `<iframe src="${url}" style="width: 100%; height: 70vh; border: none;"></iframe>`;
    } else {
        // Other file types - show download link
        modalContent.innerHTML = `
            <div class="alert alert-info">
                <p>This file type cannot be previewed. Please download to view.</p>
                <a href="${url}" download class="btn btn-primary">
                    <i class="fas fa-download"></i> Download ${title}
                </a>
            </div>
        `;
    }
    
    modal.show();
}

function saveBankField(fieldName, inputId) {
    const input = document.getElementById(inputId);
    const value = input.value.trim();
    
    // UPI ID is optional, so allow empty value
    if (!value && fieldName !== 'upi_id') {
        if (typeof toastr !== 'undefined') {
            toastr.error('Please enter a value');
        } else {
            alert('Please enter a value');
        }
        return;
    }

    // Disable input and button during save
    input.disabled = true;
    const button = input.nextElementSibling;
    const originalButtonHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    $.ajax({
        url: '{{ route("doctor_portal.bank-details.update-field") }}',
        type: 'POST',
        data: {
            field_name: fieldName,
            field_value: value,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message || 'Field updated successfully');
                } else {
                    alert(response.message || 'Field updated successfully');
                }
                // Reload page after 1 second to show updated value
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(response.message || 'Failed to update field');
                } else {
                    alert(response.message || 'Failed to update field');
                }
                input.disabled = false;
                button.disabled = false;
                button.innerHTML = originalButtonHtml;
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to update field';
            if (typeof toastr !== 'undefined') {
                toastr.error(errorMsg);
            } else {
                alert(errorMsg);
            }
            input.disabled = false;
            button.disabled = false;
            button.innerHTML = originalButtonHtml;
        }
    });
}

function uploadBankDocument(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const formData = new FormData();
        formData.append('document', file);
        formData.append('document_type', 'bank_document');
        formData.append('_token', '{{ csrf_token() }}');

        // Disable input and show loading
        const $input = $(input);
        const $cell = $('#bankDocumentCell');
        const originalContent = $cell.html();
        $input.prop('disabled', true);
        $cell.html('<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Uploading...</span>');

        // Upload to server
        $.ajax({
            url: '{{ route("doctor_portal.bank-details.upload-document") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || 'Bank document uploaded successfully');
                    } else {
                        alert(response.message || 'Bank document uploaded successfully');
                    }
                    
                    // Update the cell with view/download buttons
                    const documentUrl = response.document_url;
                    $cell.html(`
                        <button type="button" class="btn btn-sm bank-btn bank-btn-view" onclick="viewDocument('${documentUrl}', 'Bank Document')">
                            <i class="fas fa-eye mr-1"></i>View
                        </button>
                        <a href="${documentUrl}" download class="btn btn-sm bank-btn bank-btn-download">
                            <i class="fas fa-download mr-1"></i>Download
                        </a>
                    `);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'Failed to upload bank document');
                    } else {
                        alert(response.message || 'Failed to upload bank document');
                    }
                    $cell.html(originalContent);
                    $input.prop('disabled', false);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to upload bank document';
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMsg);
                } else {
                    alert(errorMsg);
                }
                $cell.html(originalContent);
                $input.prop('disabled', false);
            }
        });
    }
}
</script>
<style>
    .dr-bank-wrapper {
        overflow-y: auto;
        max-height: calc(100vh - 120px);
        background: #f5f7fb;
    }
    .dr-bank-card {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }
    .dr-bank-header {
        background: linear-gradient(90deg, #f7941d 0%, #ff7a18 100%);
        color: #fff;
        padding: 1rem 1.25rem;
    }
    .dr-bank-header p {
        opacity: 0.95;
        font-size: 0.88rem;
    }
    .dr-bank-body {
        max-height: calc(100vh - 250px);
        overflow-y: auto;
        padding: 1.25rem;
        background: #f5f7fb;
    }
    .bank-field-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        height: 100%;
    }
    .bank-label {
        display: block;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    .bank-value {
        color: #0f172a;
        font-weight: 600;
        word-break: break-word;
    }
    .bank-input-wrap {
        gap: 0.6rem;
    }
    .bank-input-wrap .form-control {
        border-radius: 9px;
        border-color: #dbe2ea;
        box-shadow: none;
    }
    .bank-btn {
        border: 0;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.8rem;
        padding: 0.38rem 0.72rem;
    }
    .bank-btn-save {
        background: #0ea5e9;
        color: #fff;
        white-space: nowrap;
    }
    .bank-btn-save:hover {
        background: #0284c7;
        color: #fff;
    }
    .bank-btn-view {
        background: #0ea5e9;
        color: #fff;
    }
    .bank-btn-view:hover {
        background: #0284c7;
        color: #fff;
    }
    .bank-btn-download {
        background: #22c55e;
        color: #fff;
    }
    .bank-btn-download:hover {
        background: #16a34a;
        color: #fff;
    }
    .bank-btn-upload {
        background: #f59e0b;
        color: #fff;
    }
    .bank-btn-upload:hover {
        background: #d97706;
        color: #fff;
    }
    /* Custom scrollbar styling */
    .content-wrapper::-webkit-scrollbar,
    .card-body::-webkit-scrollbar {
        width: 8px;
    }
    
    .content-wrapper::-webkit-scrollbar-track,
    .card-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .content-wrapper::-webkit-scrollbar-thumb,
    .card-body::-webkit-scrollbar-thumb {
        background: #F7941D;
        border-radius: 10px;
    }
    
    .content-wrapper::-webkit-scrollbar-thumb:hover,
    .card-body::-webkit-scrollbar-thumb:hover {
        background: #d97706;
    }
    
    /* Firefox scrollbar */
    .content-wrapper,
    .card-body {
        scrollbar-width: thin;
        scrollbar-color: #F7941D #f1f1f1;
    }
    @media (max-width: 767px) {
        .dr-bank-body {
            padding: 0.95rem;
        }
        .bank-input-wrap {
            flex-direction: column;
            align-items: stretch !important;
        }
    }
</style>
@endsection

