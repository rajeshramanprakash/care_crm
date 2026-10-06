@php
    $suBadge = ['new' => 'primary', 'under_review' => 'warning', 'resolved' => 'success'][$submission->status] ?? 'secondary';
@endphp
<span class="badge badge-{{ $suBadge }} bg-{{ $suBadge }}">{{ $submission->status_label }}</span>
