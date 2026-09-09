@extends('doctor_carelix.layouts.app')
@section('title', 'Website bookings')

@section('main')
@php
    use App\Models\ConsultationWebsiteBooking;
@endphp
<div class="content-wrapper dr-bookings-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-12">
                    <div class="card dr-bookings-card">
                        <div class="card-header dr-bookings-header">
                            <h3 class="card-title mb-1">Consultation website bookings</h3>
                        </div>
                        <div class="card-body dr-bookings-body">
                            @php
                                $list = $doctor->consultationWebsiteBookings ?? collect();
                            @endphp
                            @if($list->isEmpty())
                                <div class="dr-empty-state">
                                    <i class="fas fa-calendar-times mb-2"></i>
                                    <h5 class="mb-2">No bookings yet</h5>
                                    <p class="mb-0">Bookings submitted from the website will appear here.</p>
                                </div>
                            @else
                                <div class="table-responsive dr-table-card">
                                    <table class="table dr-bookings-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Submitted at</th>
                                                <th>Appointment date</th>
                                                <th>Time slot</th>
                                                <th>Customer</th>
                                                <th>Address</th>
                                                <th>City</th>
                                                <th>Map</th>
                                                <th>Mode</th>
                                                <th>Meeting link</th>
                                                <th>Duration</th>
                                                <th>Service (page)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($list as $b)
                                                <tr>
                                                    <td>{{ $b->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                                                    <td>{{ $b->appointment_date?->format('d M Y') ?? '—' }}</td>
                                                    <td>{{ ConsultationWebsiteBooking::timeRangeLabel($b->appointment_start_time, $b->appointment_end_time) }}</td>
                                                    <td>{{ $b->customer_name }}</td>
                                                    <td class="dr-cell-wrap">{{ $b->customer_address ?: '—' }}</td>
                                                    <td>{{ $b->customer_city ?: '—' }}</td>
                                                    <td class="small">
                                                        @php
                                                            $dMap = ConsultationWebsiteBooking::googleMapsOpenUrl(
                                                                $b->customer_address_lat !== null ? (float) $b->customer_address_lat : null,
                                                                $b->customer_address_lng !== null ? (float) $b->customer_address_lng : null
                                                            );
                                                        @endphp
                                                        @if($dMap)
                                                            <a href="{{ $dMap }}" target="_blank" rel="noopener">Open</a>
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php($mode = ConsultationWebsiteBooking::modeLabel($b->consultation_mode))
                                                        <span class="dr-badge dr-badge-mode">{{ $mode }}</span>
                                                    </td>
                                                    <td>
                                                        @if(!empty($b->online_meeting_link))
                                                            <a class="btn btn-sm dr-btn-primary" href="{{ route('doctor_portal.bookings.join-meeting', ['bookingId' => $b->id]) }}">
                                                                <i class="fas fa-video mr-1"></i>Open
                                                            </a>
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>{{ ConsultationWebsiteBooking::durationLabel($b->consultation_duration_minutes ?? $b->consultationService?->consultation_duration_minutes) }}</td>
                                                    <td>{{ $b->consultationService?->name ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
    .dr-bookings-wrapper {
        overflow-y: auto;
        max-height: calc(100vh - 120px);
        background: #f5f7fb;
    }
    .dr-bookings-card {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }
    .dr-bookings-header {
        background: linear-gradient(90deg, #f7941d 0%, #ff7a18 100%);
        color: #fff;
        padding: 1rem 1.25rem;
        border: 0;
    }
    .dr-bookings-header p {
        opacity: 0.95;
        font-size: 0.88rem;
    }
    .dr-bookings-body {
        background: #f5f7fb;
        padding: 1.25rem;
    }
    .dr-table-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    }
    .dr-bookings-table thead th {
        background: #f8fafc;
        color: #334155;
        border-bottom: 1px solid #dbe2ea;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.35px;
        white-space: nowrap;
        padding: 0.75rem 0.75rem;
    }
    .dr-bookings-table td {
        vertical-align: middle;
        border-top: 1px solid #edf2f7;
        color: #0f172a;
        font-size: 0.9rem;
        padding: 0.7rem 0.75rem;
        white-space: nowrap;
    }
    .dr-bookings-table tbody tr:hover {
        background: #f8fbff;
    }
    .dr-cell-wrap {
        white-space: normal;
        min-width: 240px;
        max-width: 360px;
    }
    .dr-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.42rem 0.7rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.2px;
        background: rgba(14, 165, 233, 0.14);
        color: #0369a1;
        white-space: nowrap;
    }
    .dr-btn-primary {
        border: 0;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.8rem;
        padding: 0.35rem 0.65rem;
        background: #0ea5e9;
        color: #fff;
    }
    .dr-btn-primary:hover {
        background: #0284c7;
        color: #fff;
    }
    .dr-empty-state {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem 1rem;
        text-align: center;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        color: #334155;
    }
    .dr-empty-state i {
        font-size: 2rem;
        color: #f7941d;
        display: block;
    }
    @media (max-width: 767px) {
        .dr-bookings-body { padding: 0.95rem; }
        .dr-cell-wrap { min-width: 200px; }
    }
</style>
@endpush
@endsection
