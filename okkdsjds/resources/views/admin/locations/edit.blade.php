@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper">

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Location</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.locations.update', $location) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="name">Location Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $location->name) }}" required>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Services & Pricing</label>
                            <div id="services-container" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; background-color: #f8f9fa;">
                                @if($location->services->count() > 0)
                                    @foreach($location->services as $index => $service)
                                        <div class="service-row border p-3 mb-3 rounded" style="background-color: white;">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label>Service</label>
                                                    <select class="form-control service-select" name="services[{{ $index }}][service_id]" required>
                                                        <option value="">Select Service</option>
                                                        @foreach($services as $s)
                                                            <option value="{{ $s->id }}" {{ $s->id == $service->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label>12hr Price</label>
                                                    <input type="number" step="0.01" class="form-control" name="services[{{ $index }}][price_12hr]" value="{{ $service->pivot->price_12hr }}" placeholder="0.00">
                                                </div>
                                                <div class="col-md-2">
                                                    <label>24hr Price</label>
                                                    <input type="number" step="0.01" class="form-control" name="services[{{ $index }}][price_24hr]" value="{{ $service->pivot->price_24hr }}" placeholder="0.00">
                                                </div>
                                                <div class="col-md-2">
                                                    <label>One-time Price</label>
                                                    <input type="number" step="0.01" class="form-control" name="services[{{ $index }}][price_onetime]" value="{{ $service->pivot->price_onetime }}" placeholder="0.00">
                                                </div>
                                                <div class="col-md-2">
                                                    <label>&nbsp;</label>
                                                    <button type="button" class="btn btn-danger btn-sm btn-block remove-service" {{ $location->services->count() == 1 ? 'style=display:none;' : '' }}>
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="service-row border p-3 mb-3 rounded" style="background-color: white;">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label>Service</label>
                                                <select class="form-control service-select" name="services[0][service_id]" required>
                                                    <option value="">Select Service</option>
                                                    @foreach($services as $service)
                                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label>12hr Price</label>
                                                <input type="number" step="0.01" class="form-control" name="services[0][price_12hr]" placeholder="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label>24hr Price</label>
                                                <input type="number" step="0.01" class="form-control" name="services[0][price_24hr]" placeholder="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label>One-time Price</label>
                                                <input type="number" step="0.01" class="form-control" name="services[0][price_onetime]" placeholder="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label>&nbsp;</label>
                                                <button type="button" class="btn btn-danger btn-sm btn-block remove-service" style="display: none;">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-success btn-sm mt-2" id="add-service">
                                <i class="fas fa-plus"></i> Add Another Service
                            </button>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Location</button>
                        <a href="{{ route('admin.locations.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let serviceIndex = {{ $location->services->count() }};

    // Add service functionality
    document.getElementById('add-service').addEventListener('click', function() {
        const container = document.getElementById('services-container');
        const newServiceRow = document.createElement('div');
        newServiceRow.className = 'service-row border p-3 mb-3 rounded';
        newServiceRow.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <label>Service</label>
                    <select class="form-control service-select" name="services[${serviceIndex}][service_id]" required>
                        <option value="">Select Service</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>12hr Price</label>
                    <input type="number" step="0.01" class="form-control" name="services[${serviceIndex}][price_12hr]" placeholder="0.00">
                </div>
                <div class="col-md-2">
                    <label>24hr Price</label>
                    <input type="number" step="0.01" class="form-control" name="services[${serviceIndex}][price_24hr]" placeholder="0.00">
                </div>
                <div class="col-md-2">
                    <label>One-time Price</label>
                    <input type="number" step="0.01" class="form-control" name="services[${serviceIndex}][price_onetime]" placeholder="0.00">
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm btn-block remove-service">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
            </div>
        `;
        newServiceRow.style.backgroundColor = 'white';

        container.appendChild(newServiceRow);
        serviceIndex++;

        // Show remove buttons for all rows
        document.querySelectorAll('.remove-service').forEach(btn => {
            btn.style.display = 'block';
        });
    });

    // Remove service functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-service')) {
            e.target.closest('.service-row').remove();

            // Hide remove buttons if only one row left
            const remainingRows = document.querySelectorAll('.service-row');
            if (remainingRows.length === 1) {
                document.querySelector('.remove-service').style.display = 'none';
            }
        }
    });
});
</script>

@endsection
