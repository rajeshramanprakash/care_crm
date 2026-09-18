@php
    $isEdit = isset($location);
    $tierOptions = $tierOptions ?? ['Tier 1', 'Tier 2', 'Tier 3', 'Tier 4'];
    $selectedState = old('state', $isEdit ? $location->state : '');
    $selectedTier = old('tier', $isEdit ? $location->tier : '');
@endphp

<form action="{{ $formAction }}" method="POST" class="loc-form">
    @csrf
    @if(!empty($formMethod))
        @method($formMethod)
    @endif

    <div class="loc-card">
        <div class="loc-card-head"><i class="fas fa-map-marker-alt"></i> Location</div>
        <div class="loc-card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="loc-label" for="state">State <span class="text-danger">*</span></label>
                        <select class="form-control @error('state') is-invalid @enderror" id="state" name="state" required>
                            <option value="">Select state</option>
                            @foreach($indianStates as $stateName)
                                <option value="{{ $stateName }}" {{ (string) $selectedState === (string) $stateName ? 'selected' : '' }}>{{ $stateName }}</option>
                            @endforeach
                        </select>
                        @error('state')
                            <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="loc-label" for="tier">Tier <span class="text-danger">*</span></label>
                        <select class="form-control @error('tier') is-invalid @enderror" id="tier" name="tier" required>
                            <option value="">Select tier</option>
                            @foreach($tierOptions as $tierOption)
                                <option value="{{ $tierOption }}" {{ (string) $selectedTier === (string) $tierOption ? 'selected' : '' }}>{{ $tierOption }}</option>
                            @endforeach
                        </select>
                        @error('tier')
                            <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-md-0">
                        <label class="loc-label" for="name">City / Town name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $isEdit ? $location->name : '') }}"
                               placeholder="e.g. Mumbai"
                               required>
                        @error('name')
                            <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
            </div>
            <p class="text-muted small mb-0 mt-2">Pehle state aur tier choose karein, phir city. Neeche is location ke liye doctor, vendor aur freelancer pricing alag save hogi.</p>
        </div>
    </div>

    <div class="loc-card">
        <div class="loc-card-head"><i class="fas fa-rupee-sign"></i> Pricing (Doctor · Vendor · Freelancer)</div>
        <div class="loc-card-body">
            @include('admin.locations.partials.pricing-sections')
        </div>
    </div>

    <div class="loc-form-footer">
        <button type="submit" class="btn btn-loc-primary">
            <i class="fas fa-check mr-1"></i> {{ $submitLabel }}
        </button>
        <a href="{{ route('subadmin.locations.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
