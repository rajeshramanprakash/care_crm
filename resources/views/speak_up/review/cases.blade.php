@extends('admin.layouts.app')
@section('title', 'Speak Up Cases')

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            @include('speak_up.review._nav')
            <div class="card" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                <div class="card-body">
                    <div class="d-flex flex-wrap mb-3" style="gap:6px;">
                        @php
                            $tabs = ['' => 'All (' . $counts->sum() . ')'];
                            foreach (\App\Models\SpeakUpSubmission::STATUSES as $key => $label) { $tabs[$key] = $label . ' (' . ($counts[$key] ?? 0) . ')'; }
                            $tabs['anonymous'] = 'Anonymous';
                            $tabs['named'] = 'With name';
                        @endphp
                        @foreach ($tabs as $key => $label)
                            <a href="{{ route('speak_up.review.cases', array_filter([$slug, 'filter' => $key ?: null])) }}" class="btn btn-sm {{ (string) $filter === (string) $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                    <form method="GET" class="row g-2 mb-3">
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        <div class="col-md-4 mb-2">
                            <select name="category" class="form-control form-select form-select-sm">
                                <option value="">All categories</option>
                                @foreach (\App\Models\SpeakUpSubmission::CATEGORIES as $key => $label)
                                    <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5 mb-2"><input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search reference, subject, text"></div>
                        <div class="col-md-3 mb-2"><button class="btn btn-sm btn-dark w-100 btn-block">Filter</button></div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead><tr><th>Reference</th><th>Date</th><th>Category</th><th>Subject</th><th>From</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            @forelse ($submissions as $submission)
                                <tr>
                                    <td class="text-nowrap">{{ $submission->reference_no }}</td>
                                    <td class="text-nowrap">{{ $submission->created_at->format('d M Y, h:i A') }}</td>
                                    <td>{{ $submission->category_label }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($submission->subject, 60) }}</td>
                                    <td>
                                        @if ($submission->is_anonymous)
                                            <span class="badge bg-secondary badge-secondary">Anonymous</span>
                                        @else
                                            {{ $submission->submitter_name }} <small class="text-muted">({{ $submission->submitter_role }})</small>
                                        @endif
                                    </td>
                                    <td>@include('speak_up._status_badge')</td>
                                    <td><a href="{{ route('speak_up.review.show', [$slug, $submission->id]) }}" class="btn btn-sm btn-outline-dark">Open</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No submissions.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $submissions->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
