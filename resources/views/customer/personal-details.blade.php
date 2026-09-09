@extends('customer.layouts.app')
@section('title', 'Personal Details')

@section('content')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="customer-profile-page">
                <div class="customer-profile-hero">
                    <div class="customer-avatar-wrap">
                        <img id="profileImagePreview"
                             src="{{ $customer->profile_image ? asset('storage/' . $customer->profile_image) : asset('images/default-user.png') }}"
                             alt="Profile Image"
                             class="customer-avatar"
                             onclick="document.getElementById('profileImageInput').click()">
                        <button type="button" class="avatar-camera-btn" onclick="document.getElementById('profileImageInput').click()">
                            <i class="fas fa-camera"></i>
                        </button>
                        <input type="file" id="profileImageInput" accept="image/*" style="display: none;" onchange="uploadProfileImage(this)">
                    </div>
                    <div class="customer-hero-meta">
                        <h2 class="customer-hero-name">{{ $customer->customer_name ?? 'N/A' }}</h2>
                        <p class="customer-hero-sub">Customer profile details</p>
                        <p class="customer-hero-query">{{ $customer->query ?? 'Care requirement not specified yet.' }}</p>
                        <p class="customer-hero-location">{{ $customer->location ?? $customer->address ?? 'India' }}</p>
                    </div>
                </div>

                <div class="customer-detail-card">
                    <div class="customer-detail-card-title">Contact Details</div>
                    <table class="table customer-details-table mb-0">
                        <tr>
                            <th>Customer Name</th>
                            <td>{{ $customer->customer_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Contact Number</th>
                            <td>{{ $customer->contact_no ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>{{ $customer->address ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Location</th>
                            <td>{{ $customer->location ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>

                <div class="customer-detail-card mb-0">
                    <div class="customer-detail-card-title">Account Summary</div>
                    <table class="table customer-details-table mb-0">
                        <tr>
                            <th>Status</th>
                            <td>
                                @if(isset($customer->status) && $customer->status)
                                    <span class="customer-status-pill">{{ ucfirst($customer->status) }}</span>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Query</th>
                            <td>{{ $customer->query ?? 'N/A' }}</td>
                        </tr>
                        @if($customerType === 'operation_lead')
                        <tr>
                            <th>Query Remark</th>
                            <td>{{ $customer->query_remark ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Shift Type</th>
                            <td>{{ $customer->shift_type ?? 'N/A' }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
                <p class="text-muted small mt-2 mb-0">Click on profile image to upload photo.</p>
            </div>
        </div>
    </section>
</div>

<style>
.customer-profile-page { background: #f6f8fb; border-radius: 16px; padding: 18px; }
.customer-profile-hero { display: flex; gap: 18px; background: #fff; border-radius: 14px; padding: 16px; border: 1px solid #e8edf4; }
.customer-avatar-wrap { position: relative; flex-shrink: 0; }
.customer-avatar { width: 180px; height: 180px; border-radius: 18px; object-fit: cover; border: 3px solid #f7941d; cursor: pointer; }
.avatar-camera-btn { position: absolute; right: 8px; bottom: 8px; border: none; background: #f7941d; color: #fff; width: 36px; height: 36px; border-radius: 50%; }
.customer-hero-name { margin: 0; font-size: 2rem; font-weight: 700; color: #1f2937; }
.customer-hero-sub { color: #0f8aa0; font-weight: 600; margin: 2px 0 10px; }
.customer-hero-query { font-size: 1.05rem; margin: 0 0 4px; color: #111827; }
.customer-hero-location { margin: 0; color: #4b5563; }
.customer-detail-card { margin-top: 12px; border: 1px solid #e4e9f2; border-radius: 12px; overflow: hidden; background: #fff; }
.customer-detail-card-title { padding: 12px 14px; font-weight: 700; font-size: 1.2rem; color: #1f2937; border-bottom: 1px solid #eef2f7; }
.customer-details-table th, .customer-details-table td { border-top: 1px solid #edf1f6; padding: 11px 14px; }
.customer-details-table th { width: 35%; color: #334155; font-weight: 600; background: #fafbfd; }
.customer-details-table td { color: #1f2937; font-weight: 600; }
.customer-status-pill { display: inline-flex; align-items: center; border-radius: 999px; background: #e8f8ef; border: 1px solid #bde9cf; color: #177a43; padding: 5px 12px; font-size: 0.82rem; text-transform: capitalize; }
@media (max-width: 991px) {
    .customer-profile-hero { flex-direction: column; }
    .customer-avatar { width: 140px; height: 140px; border-radius: 12px; }
    .customer-hero-name { font-size: 1.6rem; }
}
@media (max-width: 576px) {
    .customer-profile-page { padding: 12px; }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function uploadProfileImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const formData = new FormData();
        formData.append('profile_image', file);
        formData.append('_token', '{{ csrf_token() }}');

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profileImagePreview').src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Upload to server
        $.ajax({
            url: '{{ route("customer.personal-details.update-profile-image") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || 'Profile image updated successfully');
                    } else {
                        alert(response.message || 'Profile image updated successfully');
                    }
                    // Update image source with new URL
                    if (response.profile_image_url) {
                        document.getElementById('profileImagePreview').src = response.profile_image_url;
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'Failed to update profile image');
                    } else {
                        alert(response.message || 'Failed to update profile image');
                    }
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to update profile image';
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMsg);
                } else {
                    alert(errorMsg);
                }
            }
        });
    }
}
</script>
@endsection

