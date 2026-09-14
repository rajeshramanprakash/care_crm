@extends('admin.layouts.app')

@section('title', 'Bulk Price Increase / Decrease')

@section('header-css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--multiple {
        border: 1px solid #cfd8dc;
        border-radius: 12px;
        min-height: 48px;
        padding: 5px 14px;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #e39460;
    }
    .bulk-card {
      background:#fff;
      border-radius:18px;
      padding:24px;
      border:1px solid #e5e7eb;
      box-shadow:0 10px 30px rgba(0,0,0,0.05);
      margin:auto;
      font-family:Inter, Arial, sans-serif;
      color:#1f2933;
    }

    .bulk-title {
      font-size:26px;
      font-weight:800;
      margin-bottom:24px;
      color:#28392b;
    }

    .bulk-grid {
      display:grid;
      grid-template-columns:repeat(4,1fr);
      gap:18px;
      margin-bottom:18px;
    }

    .bulk-field label {
      display:block;
      margin-bottom:8px;
      font-size:14px;
      font-weight:700;
      color:#243b35;
    }

    .bulk-field select,
    .bulk-field input {
      width:100%;
      height:48px;
      border:1px solid #cfd8dc;
      border-radius:12px;
      padding:0 14px;
      font-size:15px;
      outline:none;
      background:#fff;
    }

    .bulk-field select:focus,
    .bulk-field input:focus {
      border-color:#e39460;
      box-shadow:0 0 0 3px rgba(227,148,96,0.15);
    }

    .bulk-buttons {
      display:flex;
      gap:14px;
      margin-top:24px;
    }

    .bulk-buttons button {
      border:0;
      border-radius:12px;
      padding:14px 20px;
      font-size:14px;
      font-weight:800;
      cursor:pointer;
    }

    .bulk-btn-primary {
      background:#e39460;
      color:#fff;
    }

    .bulk-btn-secondary {
      background:#eef2f4;
      color:#28392b;
    }

    .bulk-info-box {
      margin-top:20px;
      background:#fff7ed;
      border:1px solid #fed7aa;
      color:#7c2d12;
      padding:14px;
      border-radius:12px;
      font-size:14px;
      font-weight:600;
    }

    @media(max-width:1000px){
      .bulk-grid {
        grid-template-columns:1fr;
      }
    }
</style>
@endsection

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Bulk Registration / Price Update</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Bulk Price Increase</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="bulk-card">
                <div class="bulk-title">
                  Bulk Price Increase / Decrease
                </div>

                <form action="{{ route('admin.bulk_registration.store') }}" method="POST">
                  @csrf
                  <div class="bulk-grid">
                    <div class="bulk-field">
                      <label>Pricing Type</label>
                      <select id="pricingType" name="pricing_type" required>
                      <option value="">Select Type</option>
                      <option value="doctor_payout">Doctor Payout Only</option>
                      <option value="vendor">Vendor</option>
                      <option value="freelancer">Freelancer</option>
                      <option value="website_general">Website - General Services</option>
                      <option value="website_doctor">Website - Doctor Consultations</option>
                    </select>
                  </div>

                  <div class="bulk-field">
                    <label>Service</label>
                    <select id="serviceSelect" name="service_id">
                      <option value="">All Services</option>
                      @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="bulk-field">
                    <label>Sub Service</label>
                    <select id="subServiceSelect" name="sub_service_id">
                      <option value="">All Sub Services</option>
                    </select>
                  </div>

                  <div class="bulk-field">
                    <label>Mode / Duty Type</label>
                    <select id="modeType" name="mode_type">
                      <option value="">Select option</option>
                    </select>
                  </div>
                </div>

                <div class="bulk-grid">
                  <div class="bulk-field">
                    <label>Change Type</label>
                    <select name="change_type" required>
                      <option value="normal">Normal</option>
                      <option value="increase_fixed">Increase by Fixed Amount</option>
                      <option value="increase_percent">Increase by Percentage</option>
                      <option value="decrease_fixed">Decrease by Fixed Amount</option>
                      <option value="decrease_percent">Decrease by Percentage</option>
                    </select>
                  </div>

                  <div class="bulk-field">
                    <label>Value</label>
                    <input type="number" step="0.01" name="value" placeholder="Enter Value" required>
                  </div>

                  <div class="bulk-field">
                    <label>Apply To</label>
                    <select id="applyTo" name="apply_to" required>
                      <option value="all">All Existing + New</option>
                      <option value="new">New Registrations Only</option>
                      <option value="old">Old Registrations Only</option>
                    </select>
                    <div id="applyToDateDisplay" style="font-size:12px; color:#e39460; margin-top:6px; font-weight:700;"></div>
                    <!-- Hidden inputs to store the values for backend -->
                    <input type="hidden" id="applyToFromHidden" name="apply_from_date">
                    <input type="hidden" id="applyToToHidden" name="apply_to_date">
                  </div>

                  <div class="bulk-field">
                    <label>City Filter</label>
                    <select id="cityFilterSelect" name="city_filter">
                      <option value="current">Current City Only</option>
                      <option value="all">All Cities</option>
                      <option value="tier_1">Tier 1 Cities</option>
                      <option value="tier_2">Tier 2 Cities</option>
                      <option value="tier_3">Tier 3 Cities</option>
                    </select>
                  </div>
                  
                  <div class="bulk-field" id="selectedCitiesWrapper" style="display: none; grid-column: span 2;">
                    <label>Select Specific Cities (Leave empty to apply to all in Tier)</label>
                    <select id="selectedCitiesSelect" name="selected_cities[]" multiple="multiple" style="width: 100%;">
                    </select>
                  </div>
                </div>

                <div class="bulk-grid">
                  <div class="bulk-field">
                    <label>Time Period</label>
                    <select id="timePeriod" name="time_period" required>
                      <option value="permanent">Permanent Price Change</option>
                      <option value="temporary">Temporary Price Change</option>
                    </select>
                    <div id="timePeriodDateDisplay" style="font-size:12px; color:#e39460; margin-top:6px; font-weight:700;"></div>
                    <!-- Hidden inputs to store the values for backend -->
                    <input type="hidden" id="timePeriodFromHidden" name="time_period_start_date">
                    <input type="hidden" id="timePeriodToHidden" name="time_period_end_date">
                  </div>
                </div>

                <div class="bulk-info-box">
                  Example: Increase Vendor 24hr ICU Nurse price by ₹100 for 7 days only, then automatically revert to old price.
                </div>

                <div class="bulk-buttons">
                  <button type="submit" class="bulk-btn-primary">Apply Bulk Update</button>
                  <button type="button" class="bulk-btn-secondary">Cancel</button>
                </div>
                </form>
            </div>

            <!-- Active Bulk Rules Table -->
            <div class="bulk-card" style="margin-top: 30px;">
                <div class="bulk-title">
                  Active Bulk Pricing Rules
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="font-size:14px;">
                        <thead>
                            <tr>
                                <th>Target</th>
                                <th>Service</th>
                                <th>Mode</th>
                                <th>Change</th>
                                <th>Apply To</th>
                                <th>City</th>
                                <th>Period</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeRules as $rule)
                            <tr>
                                <td>{{ ucfirst($rule->pricing_type) }}</td>
                                <td>
                                    @if(in_array($rule->pricing_type, ['website_doctor', 'doctor_payout']))
                                        @if($rule->doctorService)
                                            {{ $rule->doctorService->name }}
                                            @if($rule->doctorSubService) <br><small class="text-muted">({{ $rule->doctorSubService->name }})</small> @endif
                                        @else
                                            All Services
                                        @endif
                                    @else
                                        @if($rule->service)
                                            {{ $rule->service->name }} 
                                            @if($rule->subService) <br><small class="text-muted">({{ $rule->subService->name }})</small> @endif
                                        @else
                                            All Services
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $rule->mode_type ? str_replace('_', ' ', ucfirst($rule->mode_type)) : 'All' }}</td>
                                <td>
                                    @if($rule->change_type === 'normal')
                                        <span class="text-primary"><i class="fas fa-check"></i> ₹{{ $rule->value }}</span>
                                    @elseif(str_contains($rule->change_type, 'increase'))
                                        <span class="text-success"><i class="fas fa-arrow-up"></i> {{ str_contains($rule->change_type, 'percent') ? $rule->value.'%' : '₹'.$rule->value }}</span>
                                    @else
                                        <span class="text-danger"><i class="fas fa-arrow-down"></i> {{ str_contains($rule->change_type, 'percent') ? $rule->value.'%' : '₹'.$rule->value }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ ucfirst($rule->apply_to) }}
                                    @if($rule->apply_from_date) <br><small>{{ $rule->apply_from_date }} to {{ $rule->apply_to_date }}</small> @endif
                                </td>
                                <td>
                                    {{ ucfirst(str_replace('_', ' ', $rule->city_filter)) }}
                                    @if(!empty($rule->selected_cities))
                                        <br><small class="text-muted">{{ count($rule->selected_cities) }} cities selected</small>
                                    @endif
                                </td>
                                <td>
                                    {{ ucfirst($rule->time_period) }}
                                    @if($rule->time_period === 'temporary' && $rule->time_period_start_date)
                                        <br><small>{{ $rule->time_period_start_date }} to {{ $rule->time_period_end_date }}</small>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">No active bulk pricing rules found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- Date Range Modal -->
<div class="modal fade" id="dateRangeModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document" style="margin-top: 100px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="dateModalTitle" style="font-size:16px; font-weight:700; color:#28392b;">Select Dates</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div style="margin-bottom: 12px;">
            <label style="font-size:13px; font-weight:600;">From Date</label>
            <input type="datetime-local" class="form-control" id="modalFromDate" style="border-radius:8px; border:1px solid #cfd8dc;">
        </div>
        <div>
            <label style="font-size:13px; font-weight:600;">To Date</label>
            <input type="datetime-local" class="form-control" id="modalToDate" style="border-radius:8px; border:1px solid #cfd8dc;">
        </div>
      </div>
      <div class="modal-footer" style="border-top:none; padding-top:0;">
        <button type="button" class="btn bulk-btn-secondary" data-bs-dismiss="modal" style="font-size:13px; padding:8px 16px;">Cancel</button>
        <button type="button" class="btn bulk-btn-primary" id="saveDateRangeBtn" style="font-size:13px; padding:8px 16px; background:#e39460; color:#fff; border:none;">Apply</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('footer-script')
<script>
    const servicesData = @json($services);
    const doctorServicesData = @json($doctorServices);
    let activeServicesList = servicesData; // Default

    const serviceSelect = document.getElementById('serviceSelect');
    const subServiceSelect = document.getElementById('subServiceSelect');

    function populateServiceSelect() {
      let options = '<option value="">All Services</option>';
      activeServicesList.forEach(s => {
        options += `<option value="${s.id}">${s.name}</option>`;
      });
      serviceSelect.innerHTML = options;
      subServiceSelect.innerHTML = '<option value="">All Sub Services</option>';
    }

    serviceSelect.addEventListener('change', function() {
      const selectedServiceId = this.value;
      let subOptions = '<option value="">All Sub Services</option>';

      if (selectedServiceId) {
        const selectedService = activeServicesList.find(s => s.id == selectedServiceId);
        if (selectedService && selectedService.sub_services) {
          selectedService.sub_services.forEach(sub => {
            subOptions += `<option value="${sub.id}">${sub.name}</option>`;
          });
        }
      }
      subServiceSelect.innerHTML = subOptions;
    });

    const pricingType = document.getElementById('pricingType');
    const modeType = document.getElementById('modeType');
    const timePeriod = document.getElementById('timePeriod');
    const timePeriodDateDisplay = document.getElementById('timePeriodDateDisplay');
    const timePeriodFromHidden = document.getElementById('timePeriodFromHidden');
    const timePeriodToHidden = document.getElementById('timePeriodToHidden');

    const applyTo = document.getElementById('applyTo');
    const applyToDateDisplay = document.getElementById('applyToDateDisplay');
    const applyToFromHidden = document.getElementById('applyToFromHidden');
    const applyToToHidden = document.getElementById('applyToToHidden');

    let currentDateTarget = ''; // 'applyTo' or 'timePeriod'
    
    // Initialize Bootstrap 5 Modal
    const dateRangeModalElement = document.getElementById('dateRangeModal');
    let dateModalInstance = null;
    if (typeof bootstrap !== 'undefined') {
        dateModalInstance = new bootstrap.Modal(dateRangeModalElement);
    }

    pricingType.addEventListener('change', function(){
      let options = '';

      if(this.value === 'doctor_payout' || this.value === 'website_doctor'){
        activeServicesList = doctorServicesData;
        populateServiceSelect();

        options = `
          <option value="">Select option</option>
          <option value="Online">Online</option>
          <option value="Home Visit">Home Visit</option>
          <option value="Clinic">Clinic</option>
        `;
      }
      else if(this.value === 'vendor' || this.value === 'freelancer' || this.value === 'website_general'){
        activeServicesList = servicesData;
        populateServiceSelect();

        options = `
          <option value="">Select option</option>
          <option value="12_hours">12 Hours</option>
          <option value="24_hours">24 Hours</option>
          <option value="both">Both</option>
          <option value="one_time">One-time</option>
        `;
      }
      else{
        activeServicesList = servicesData;
        populateServiceSelect();

        options = `
          <option value="">Select option</option>
        `;
      }

      modeType.innerHTML = options;
    });

    applyTo.addEventListener('change', function() {
        if(this.value === 'new' || this.value === 'old') {
            currentDateTarget = 'applyTo';
            document.getElementById('dateModalTitle').innerText = 'Select Registration Dates';
            document.getElementById('modalFromDate').value = applyToFromHidden.value;
            document.getElementById('modalToDate').value = applyToToHidden.value;
            if (dateModalInstance) dateModalInstance.show();
            else $('#dateRangeModal').modal('show');
        } else {
            applyToDateDisplay.innerText = '';
            applyToFromHidden.value = '';
            applyToToHidden.value = '';
        }
    });

    timePeriod.addEventListener('change', function() {
        if(this.value === 'temporary') {
            currentDateTarget = 'timePeriod';
            document.getElementById('dateModalTitle').innerText = 'Select Temporary Period';
            document.getElementById('modalFromDate').value = timePeriodFromHidden.value;
            document.getElementById('modalToDate').value = timePeriodToHidden.value;
            if (dateModalInstance) dateModalInstance.show();
            else $('#dateRangeModal').modal('show');
        } else {
            timePeriodDateDisplay.innerText = '';
            timePeriodFromHidden.value = '';
            timePeriodToHidden.value = '';
        }
    });

    document.getElementById('saveDateRangeBtn').addEventListener('click', function() {
        const fromDate = document.getElementById('modalFromDate').value;
        const toDate = document.getElementById('modalToDate').value;
        
        if (!fromDate || !toDate) {
            alert('Please select both From Date and To Date.');
            return;
        }
        
        let displayStr = `${fromDate.replace('T', ' ')} to ${toDate.replace('T', ' ')}`;
        
        if (currentDateTarget === 'applyTo') {
            applyToDateDisplay.innerText = displayStr;
            applyToFromHidden.value = fromDate;
            applyToToHidden.value = toDate;
        } else if (currentDateTarget === 'timePeriod') {
            timePeriodDateDisplay.innerText = displayStr;
            timePeriodFromHidden.value = fromDate;
            timePeriodToHidden.value = toDate;
        }
        
        if (dateModalInstance) dateModalInstance.hide();
        else $('#dateRangeModal').modal('hide');
    });

    // Select2 and City Filter Logic
    if (typeof $ !== 'undefined') {
        $(document).ready(function() {
            if($.fn.select2) {
                $('#selectedCitiesSelect').select2({
                    placeholder: "Select cities...",
                    allowClear: true
                });
            }
            
            $('#cityFilterSelect').on('change', function() {
                let tier = $(this).val();
                if (tier === 'tier_1' || tier === 'tier_2' || tier === 'tier_3') {
                    $('#selectedCitiesWrapper').show();
                    
                    // Fetch cities via AJAX
                    $.ajax({
                        url: '{{ route("admin.bulk_registration.cities_by_tier") }}',
                        type: 'GET',
                        data: { tier: tier },
                        success: function(response) {
                            if (response.success) {
                                let options = '';
                                response.cities.forEach(function(city) {
                                    options += `<option value="${city.id}">${city.name}</option>`;
                                });
                                $('#selectedCitiesSelect').html(options);
                            }
                        }
                    });
                } else {
                    $('#selectedCitiesWrapper').hide();
                    $('#selectedCitiesSelect').html('');
                }
            });
        });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection
