@extends('admin.layouts.app')


@section('content')
@php
    $formatValue = function ($value) {
        if (is_null($value)) {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return (string) $value;
    };
@endphp
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Activity #{{ $log->id }}</h1>
                </div>
                <div class="col-sm-6 text-end">
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.subadmin_activity.index') }}" class="btn btn-secondary btn-sm">Back</a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th style="width: 180px;">Sub Admin</th><td>{{ $log->user_name }} (#{{ $log->user_id }})</td></tr>
                        <tr><th>Date / Time</th><td>{{ $log->created_at?->format('d M Y, h:i:s A') }}</td></tr>
                        <tr><th>Action</th><td>{{ ucfirst($log->action) }}</td></tr>
                        <tr><th>Module</th><td>{{ $log->module }}</td></tr>
                        <tr><th>Description</th><td>{{ $log->description }}</td></tr>
                        <tr><th>Request</th><td>{{ $log->method }} {{ $log->url }}</td></tr>
                        <tr><th>Route</th><td>{{ $log->route_name }}</td></tr>
                        <tr><th>Status</th><td>{{ $log->status_code }}</td></tr>
                        <tr><th>IP / Browser</th><td>{{ $log->ip_address }} <div class="text-muted small">{{ $log->user_agent }}</div></td></tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Data Changes</h3></div>
                <div class="card-body">
                    @forelse($log->changes ?? [] as $change)
                        <h6 class="mt-2">
                            {{ ucfirst($change['event'] ?? '') }} {{ $change['model'] ?? '' }} #{{ $change['id'] ?? '' }}
                        </h6>
                        @php
                            $fields = array_unique(array_merge(array_keys($change['old'] ?? []), array_keys($change['new'] ?? [])));
                        @endphp
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 200px;">Field</th>
                                        @if(isset($change['old']))<th>Old value</th>@endif
                                        @if(isset($change['new']))<th>New value</th>@endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fields as $field)
                                        <tr>
                                            <td>{{ $field }}</td>
                                            @if(isset($change['old']))<td style="word-break: break-all;">{{ $formatValue($change['old'][$field] ?? null) }}</td>@endif
                                            @if(isset($change['new']))<td style="word-break: break-all;">{{ $formatValue($change['new'][$field] ?? null) }}</td>@endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Is request mein koi database record change nahi hua.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Submitted Data</h3></div>
                <div class="card-body">
                    @if(!empty($log->request_data))
                        <pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($log->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    @else
                        <p class="text-muted mb-0">Koi data submit nahi hua.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
