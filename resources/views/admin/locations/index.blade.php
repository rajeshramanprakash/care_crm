@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper">

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h3 class="card-title mb-0">Locations</h3>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="input-group input-group-sm" style="width: 250px; margin: 0 auto;">
                                <input type="text" id="locationSearch" class="form-control" placeholder="Search locations...">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 text-right">
                            <a href="{{ route('admin.locations.bulk-template') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-download"></i> Download Template
                            </a>
                            <button type="button" class="btn btn-success btn-sm" id="openBulkUploadModal" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                                <i class="fas fa-upload"></i> Bulk Upload
                            </button>
                            <a href="{{ route('admin.locations.create') }}" class="btn btn-primary btn-sm">
                                Add New Location
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('import_errors') && count(session('import_errors')) > 0)
                        <div class="alert alert-warning">
                            <strong>Import warnings:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach(session('import_errors') as $importError)
                                    <li>{{ $importError }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div style="max-height: 600px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px;">
                        <table class="table table-bordered table-striped mb-0" id="locationsTable">
                            <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10;">
                                <tr>
                                    <th style="font-size: 14px; text-align:center;">ID</th>
                                    <th style="font-size: 14px; text-align:center;">City / Town</th>
                                    <th style="font-size: 14px; text-align:center;">State</th>
                                    <th style="font-size: 14px; text-align:center;">Tier</th>
                                    <th style="font-size: 14px; text-align:center;">Services</th>
                                    <th style="font-size: 14px; text-align:center;">Created At</th>
                                    <th style="font-size: 14px; text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($locations as $location)
                                    <tr class="location-row">
                                        <td style="font-size: 14px; text-align:center;">{{ $location->id }}</td>
                                        <td style="font-size: 14px; text-align:center;" class="location-name">{{ $location->name }}</td>
                                        <td style="font-size: 14px; text-align:center;" class="location-state">{{ $location->state ?? '—' }}</td>
                                        <td style="font-size: 14px; text-align:center;" class="location-tier">{{ $location->tier ?? '—' }}</td>
                                        <td style="font-size: 14px; text-align:center;">
                                            @if($location->services->count() > 0)
                                                @foreach($location->services as $service)
                                                    @php
                                                        $subId = (int) ($service->pivot->service_sub_service_id ?? 0);
                                                        $subLabel = $subId > 0 ? ($subServiceNames[$subId] ?? ('Sub #'.$subId)) : null;
                                                        $providerLabel = ucfirst((string) ($service->pivot->provider_type ?? 'vendor'));
                                                    @endphp
                                                    <span class="badge badge-info mr-1 mb-1">
                                                        {{ $service->name }}
                                                        @if($subLabel)
                                                            <br><small class="font-weight-bold">{{ $subLabel }}</small>
                                                        @endif
                                                        <br><small class="text-light">{{ $providerLabel }}</small>
                                                        @if($service->pivot->price_12hr || $service->pivot->price_24hr || $service->pivot->price_onetime)
                                                            <br>
                                                            <small>
                                                                @if($service->pivot->price_12hr) 12hr: ₹{{ $service->pivot->price_12hr }} @endif
                                                                @if($service->pivot->price_24hr) 24hr: ₹{{ $service->pivot->price_24hr }} @endif
                                                                @if($service->pivot->price_onetime) One-time: ₹{{ $service->pivot->price_onetime }} @endif
                                                            </small>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">No services</span>
                                            @endif
                                        </td>
                                        <td style="font-size: 14px; text-align:center;">{{ $location->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td style="font-size: 14px; text-align:center;">
                                            <a href="{{ route('admin.locations.edit', $location) }}" class="btn btn-link p-0 m-0" title="Edit">
                                                <i class="fas fa-map-marker-alt text-primary" style="font-size: 18px;"></i>
                                            </a>
                                            <form action="{{ route('admin.locations.destroy', $location) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link p-0 m-0" onclick="return confirm('Are you sure you want to delete this location?')" title="Delete">
                                                    <i class="fas fa-trash-alt text-danger" style="font-size: 18px;"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-labelledby="bulkUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUploadModalLabel">Bulk Upload Locations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.locations.bulk-import') }}" method="POST" enctype="multipart/form-data" id="bulkUploadForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <p class="mb-2"><strong>Excel columns (row 1 = headers):</strong></p>
                        <ul class="mb-2 ps-3">
                            <li><strong>City / Town Name</strong> — required</li>
                            <li><strong>State</strong> — required</li>
                            <li><strong>Tier</strong> — required</li>
                        </ul>
                        <p class="mb-0"><strong>No duplicates:</strong> Same city in database = update only. Same city twice in one file = second row skipped. Services are added manually.</p>
                    </div>
                    <div class="text-center mb-3">
                        <a href="{{ route('admin.locations.bulk-template') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download"></i> Download Sample Template (.xlsx)
                        </a>
                    </div>
                    <input type="file" class="d-none" id="bulk_file" name="file" accept=".xlsx,.xls" required>
                    <div id="bulkDropZone" class="border border-2 border-dashed rounded p-4 text-center" style="cursor: pointer; background: #f8f9fa; border-color: #adb5bd !important;">
                        <i class="fas fa-cloud-upload-alt fa-2x text-success mb-2"></i>
                        <p class="mb-1 fw-semibold">Drag & drop Excel file here</p>
                        <p class="mb-2 text-muted small">or click to browse (.xlsx, .xls — max 10MB)</p>
                        <p id="bulkFileName" class="mb-0 small text-primary fw-semibold"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="bulkUploadSubmitBtn" disabled>
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('locationSearch');
    const tableRows = document.querySelectorAll('.location-row');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();

        tableRows.forEach(function(row) {
            const locationName = row.querySelector('.location-name').textContent.toLowerCase();
            const locationState = (row.querySelector('.location-state')?.textContent || '').toLowerCase();
            const locationTier = (row.querySelector('.location-tier')?.textContent || '').toLowerCase();

            if (locationName.includes(searchTerm) || locationState.includes(searchTerm) || locationTier.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        const visibleRows = document.querySelectorAll('.location-row[style=""], .location-row:not([style*="display: none"])');

        if (visibleRows.length === 0 && searchTerm !== '') {
            if (!document.getElementById('noResultsMessage')) {
                const noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noResultsMessage';
                noResultsRow.innerHTML = `
                    <td colspan="7" style="text-align: center; padding: 20px; color: #6c757d;">
                        <i class="fas fa-search" style="font-size: 24px; margin-bottom: 10px;"></i><br>
                        No locations found matching "${searchTerm}"
                    </td>
                `;
                document.querySelector('#locationsTable tbody').appendChild(noResultsRow);
            }
        } else {
            const noResultsMessage = document.getElementById('noResultsMessage');
            if (noResultsMessage) {
                noResultsMessage.remove();
            }
        }
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            this.dispatchEvent(new Event('input'));
        }
    });
});
</script>

@endsection

@section('footer-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bulkModalEl = document.getElementById('bulkUploadModal');
    const openBulkBtn = document.getElementById('openBulkUploadModal');
    const bulkFileInput = document.getElementById('bulk_file');
    const bulkDropZone = document.getElementById('bulkDropZone');
    const bulkFileName = document.getElementById('bulkFileName');
    const bulkUploadSubmitBtn = document.getElementById('bulkUploadSubmitBtn');
    const bulkUploadForm = document.getElementById('bulkUploadForm');

    function openBulkModal() {
        if (!bulkModalEl) return;
        if (typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(bulkModalEl).show();
        } else if (window.ModalHelper) {
            ModalHelper.show('bulkUploadModal');
        }
    }

    if (openBulkBtn) {
        openBulkBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openBulkModal();
        });
    }

    function setBulkFile(file) {
        if (!file || !bulkFileInput) return;
        if (!/\.(xlsx|xls)$/i.test(file.name)) {
            alert('Please select a valid Excel file (.xlsx or .xls).');
            return;
        }
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        bulkFileInput.files = dataTransfer.files;
        if (bulkFileName) bulkFileName.textContent = file.name;
        if (bulkUploadSubmitBtn) bulkUploadSubmitBtn.disabled = false;
        if (bulkDropZone) {
            bulkDropZone.style.borderColor = '#198754';
            bulkDropZone.style.background = '#f0fff4';
        }
    }

    if (bulkDropZone && bulkFileInput) {
        bulkDropZone.addEventListener('click', function() { bulkFileInput.click(); });
        bulkFileInput.addEventListener('change', function() {
            if (bulkFileInput.files[0]) setBulkFile(bulkFileInput.files[0]);
        });
        ['dragenter', 'dragover'].forEach(function(evt) {
            bulkDropZone.addEventListener(evt, function(e) {
                e.preventDefault();
                e.stopPropagation();
                bulkDropZone.style.borderColor = '#0d6efd';
                bulkDropZone.style.background = '#eef5ff';
            });
        });
        bulkDropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            bulkDropZone.style.borderColor = '#adb5bd';
            bulkDropZone.style.background = '#f8f9fa';
        });
        bulkDropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (e.dataTransfer.files[0]) setBulkFile(e.dataTransfer.files[0]);
        });
    }

    if (bulkUploadForm) {
        bulkUploadForm.addEventListener('submit', function(e) {
            if (!bulkFileInput || !bulkFileInput.files.length) {
                e.preventDefault();
                alert('Please select an Excel file first.');
                return;
            }
            if (bulkUploadSubmitBtn) {
                bulkUploadSubmitBtn.disabled = true;
                bulkUploadSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            }
        });
    }

    if (bulkModalEl) {
        bulkModalEl.addEventListener('hidden.bs.modal', function() {
            if (bulkUploadForm) bulkUploadForm.reset();
            if (bulkFileName) bulkFileName.textContent = '';
            if (bulkUploadSubmitBtn) {
                bulkUploadSubmitBtn.disabled = true;
                bulkUploadSubmitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload';
            }
            if (bulkDropZone) {
                bulkDropZone.style.borderColor = '#adb5bd';
                bulkDropZone.style.background = '#f8f9fa';
            }
        });
    }
});
</script>
@endsection
