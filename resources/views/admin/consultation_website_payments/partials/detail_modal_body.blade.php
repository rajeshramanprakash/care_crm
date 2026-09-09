@php
    /** @var \App\Models\ConsultationWebsiteBooking $booking */
    $tags = is_array($booking->selected_tags) ? array_values(array_filter(array_map('strval', $booking->selected_tags))) : [];
    $subName = trim((string) ($booking->sub_service_name ?? ''));
    if ($subName === '') {
        $subName = trim((string) ($booking->consultationSubService?->name ?? ''));
    }
    $mapUrl = \App\Models\ConsultationWebsiteBooking::googleMapsOpenUrl(
        $booking->customer_address_lat !== null ? (float) $booking->customer_address_lat : null,
        $booking->customer_address_lng !== null ? (float) $booking->customer_address_lng : null
    );
@endphp
<dl class="cwp-detail-grid mb-0">
    <dt>Booking ID</dt>
    <dd>#{{ $booking->id }}</dd>

    <dt>Booked at</dt>
    <dd>{{ $booking->created_at?->timezone(config('app.timezone'))->format('d M Y, H:i') ?? '—' }}</dd>

    <dt>Customer name</dt>
    <dd>{{ $booking->customer_name }}</dd>

    <dt>Contact number</dt>
    <dd>{{ $booking->contact_no }}</dd>

    <dt>Address</dt>
    <dd>{{ $booking->customer_address ?: '—' }}</dd>

    <dt>City</dt>
    <dd>{{ $booking->customer_city ?: '—' }}</dd>

    @if($mapUrl)
        <dt>Map</dt>
        <dd><a href="{{ $mapUrl }}" target="_blank" rel="noopener">Open in Google Maps</a></dd>
    @endif

    <dt>Doctor</dt>
    <dd>{{ $booking->doctorRequest?->name ?? '—' }}</dd>

    <dt>Service</dt>
    <dd>{{ $booking->consultationService?->name ?? '—' }}</dd>

    <dt>Sub-service</dt>
    <dd>{{ $subName !== '' ? $subName : '—' }}</dd>

    <dt>Tags selected</dt>
    <dd>
        @if(count($tags))
            @foreach($tags as $tag)
                <span class="badge badge-light border mr-1 mb-1">{{ $tag }}</span>
            @endforeach
        @else
            —
        @endif
    </dd>

    <dt>Consultation mode</dt>
    <dd>{{ \App\Models\ConsultationWebsiteBooking::modeLabel((string) ($booking->consultation_mode ?? '')) }}</dd>

    <dt>Appointment</dt>
    <dd>
        {{ $booking->appointment_date ? $booking->appointment_date->format('d M Y') : '—' }}
        @if($booking->appointment_start_time)
            · {{ \App\Models\ConsultationWebsiteBooking::timeRangeLabel($booking->appointment_start_time, $booking->appointment_end_time) }}
        @endif
    </dd>

    <dt>Duration</dt>
    <dd>{{ \App\Models\ConsultationWebsiteBooking::durationLabel($booking->consultation_duration_minutes ?? $booking->consultationService?->consultation_duration_minutes) }}</dd>

    <dt>Booking fee</dt>
    <dd>
        @if($booking->booking_fee_amount !== null && (float) $booking->booking_fee_amount > 0)
            ₹{{ number_format((float) $booking->booking_fee_amount, 2) }}
        @else
            —
        @endif
    </dd>

    <dt>Payment status</dt>
    <dd>{{ \App\Models\ConsultationWebsiteBooking::paymentStatusLabel($booking->payment_status) }}</dd>

    <dt>Paid at</dt>
    <dd>{{ $booking->paid_at ? $booking->paid_at->timezone(config('app.timezone'))->format('d M Y, H:i') : '—' }}</dd>

    <dt>Transaction ref</dt>
    <dd>{{ $booking->easebuzz_txnid ?: '—' }}</dd>

    @if($booking->online_meeting_link)
        <dt>Meeting link</dt>
        <dd><a href="{{ $booking->online_meeting_link }}" target="_blank" rel="noopener">{{ $booking->online_meeting_link }}</a></dd>
    @endif
</dl>
