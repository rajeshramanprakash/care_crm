@extends('freelancer.layouts.app')
@section('title', 'Bank Account Details')

@section('main')
@php
use Illuminate\Support\Facades\Storage;
@endphp
<div class="content-wrapper" style="overflow-y: auto; max-height: calc(100vh - 120px);">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Bank Account Details</h3>
                        </div>
                        <div class="card-body" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 200px;">Account Name</th>
                                    <td>
                                        @if($freelancer->account_name)
                                            {{ $freelancer->account_name }}
                                        @else
                                            <div class="d-flex align-items-center" style="gap: 10px;">
                                                <input type="text" class="form-control" id="account_name_input" placeholder="Account Name" style="flex: 1;">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="saveBankField('account_name', 'account_name_input')">
                                                    <i class="fas fa-save"></i> Save
                                                </button>
                                            </div>
                                            <small class="text-muted">This field needs to be filled</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Account Number</th>
                                    <td>
                                        @if($freelancer->account_number)
                                            {{ $freelancer->account_number }}
                                        @else
                                            <div class="d-flex align-items-center" style="gap: 10px;">
                                                <input type="text" class="form-control" id="account_number_input" placeholder="Account Number" style="flex: 1;">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="saveBankField('account_number', 'account_number_input')">
                                                    <i class="fas fa-save"></i> Save
                                                </button>
                                            </div>
                                            <small class="text-muted">This field needs to be filled</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>IFSC Code</th>
                                    <td>
                                        @if($freelancer->ifsc_code)
                                            {{ $freelancer->ifsc_code }}
                                        @else
                                            <div class="d-flex align-items-center" style="gap: 10px;">
                                                <input type="text" class="form-control" id="ifsc_code_input" placeholder="IFSC Code" style="flex: 1;">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="saveBankField('ifsc_code', 'ifsc_code_input')">
                                                    <i class="fas fa-save"></i> Save
                                                </button>
                                            </div>
                                            <small class="text-muted">This field needs to be filled</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>UPI ID</th>
                                    <td>
                                        @if($freelancer->upi_id)
                                            {{ $freelancer->upi_id }}
                                        @else
                                            <div class="d-flex align-items-center" style="gap: 10px;">
                                                <input type="text" class="form-control" id="upi_id_input" placeholder="UPI ID (Optional)" style="flex: 1;">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="saveBankField('upi_id', 'upi_id_input')">
                                                    <i class="fas fa-save"></i> Save
                                                </button>
                                            </div>
                                            <small class="text-muted">This field is optional</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Bank Document</th>
                                    <td id="bankDocumentCell">
                                        @if($freelancer->bank_document)
                                            <button type="button" class="btn btn-sm btn-primary" onclick="viewDocument('{{ asset('storage/' . $freelancer->bank_document) }}', 'Bank Document')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <a href="{{ asset('storage/' . $freelancer->bank_document) }}" download class="btn btn-sm btn-success">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        @else
                                            <input type="file" id="bankDocumentInput" accept="image/*,.pdf" style="display: none;" onchange="uploadBankDocument(this)">
                                            <button type="button" class="btn btn-sm btn-warning" onclick="document.getElementById('bankDocumentInput').click()">
                                                <i class="fas fa-upload"></i> Upload Bank Document
                                            </button>
                                            <span class="text-muted ms-2">Not uploaded</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
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
        url: '{{ route("freelancer.bank-details.update-field") }}',
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
            url: '{{ route("freelancer.bank-details.upload-document") }}',
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
                        <button type="button" class="btn btn-sm btn-primary" onclick="viewDocument('${documentUrl}', 'Bank Document')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <a href="${documentUrl}" download class="btn btn-sm btn-success">
                            <i class="fas fa-download"></i> Download
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
</style>
@endsection

