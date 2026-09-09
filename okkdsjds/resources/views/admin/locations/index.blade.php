@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper">

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4">
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
                        <div class="col-md-4 text-right">
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

                    <div style="max-height: 600px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px;">
                        <table class="table table-bordered table-striped mb-0" id="locationsTable">
                            <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10;">
                                <tr>
                                    <th style="font-size: 14px; text-align:center;">ID</th>
                                    <th style="font-size: 14px; text-align:center;">Name</th>
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
                                        <td style="font-size: 14px; text-align:center;">
                                            @if($location->services->count() > 0)
                                                @foreach($location->services as $service)
                                                    <span class="badge badge-info mr-1 mb-1">
                                                        {{ $service->name }}
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('locationSearch');
    const tableRows = document.querySelectorAll('.location-row');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();

        tableRows.forEach(function(row) {
            const locationName = row.querySelector('.location-name').textContent.toLowerCase();

            if (locationName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // Show/hide table based on results
        const visibleRows = document.querySelectorAll('.location-row[style=""], .location-row:not([style*="display: none"])');
        const tableContainer = document.querySelector('#locationsTable').parentElement;

        if (visibleRows.length === 0 && searchTerm !== '') {
            // Show "No results found" message
            if (!document.getElementById('noResultsMessage')) {
                const noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noResultsMessage';
                noResultsRow.innerHTML = `
                    <td colspan="5" style="text-align: center; padding: 20px; color: #6c757d;">
                        <i class="fas fa-search" style="font-size: 24px; margin-bottom: 10px;"></i><br>
                        No locations found matching "${searchTerm}"
                    </td>
                `;
                document.querySelector('#locationsTable tbody').appendChild(noResultsRow);
            }
        } else {
            // Remove "No results found" message
            const noResultsMessage = document.getElementById('noResultsMessage');
            if (noResultsMessage) {
                noResultsMessage.remove();
            }
        }
    });

    // Clear search on escape key
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            this.dispatchEvent(new Event('input'));
        }
    });
});
</script>

@endsection
