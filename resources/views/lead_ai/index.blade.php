@extends($layout)

@section('title', 'AI Lead Insights')

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="m-0"><i class="fas fa-robot"></i> AI Lead Insights</h1>
            <small class="text-muted">AI review of each lead — calls, recordings, WhatsApp chat and remarks. Visible only to managers and Admin.</small>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if (! $configured)
                <div class="alert alert-warning">AI is not configured on this server. Add <code>GEMINI_API_KEY</code> to the server <code>.env</code>.</div>
            @endif

            @if (count($types) > 1)
                <ul class="nav nav-tabs mb-3">
                    @foreach ($types as $t)
                        <li class="nav-item">
                            <a class="nav-link {{ $type === $t ? 'active' : '' }}" href="{{ route($rp . '.ai_insights.index', ['type' => $t]) }}">{{ \App\Models\LeadAiAnalysis::TYPES[$t] }} leads</a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mb-3 d-flex flex-wrap" style="gap:6px;">
                <a href="{{ route($rp . '.ai_insights.index', ['type' => $type]) }}" class="btn btn-sm {{ request('health') ? 'btn-outline-secondary' : 'btn-secondary' }}">All ({{ $healthCounts->sum() }})</a>
                @foreach (\App\Models\LeadAiAnalysis::HEALTH as $key => $label)
                    <a href="{{ route($rp . '.ai_insights.index', ['type' => $type, 'health' => $key]) }}"
                       class="btn btn-sm {{ request('health') === $key ? 'btn-' . \App\Models\LeadAiAnalysis::HEALTH_COLORS[$key] : 'btn-outline-' . \App\Models\LeadAiAnalysis::HEALTH_COLORS[$key] }}">{{ $label }} ({{ $healthCounts[$key] ?? 0 }})</a>
                @endforeach
                <form method="GET" class="ms-auto ml-auto d-flex" style="gap:6px;">
                    <input type="hidden" name="type" value="{{ $type }}">
                    @if (request('health'))<input type="hidden" name="health" value="{{ request('health') }}">@endif
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Name, mobile or lead no.">
                    <button class="btn btn-sm btn-primary">Search</button>
                </form>
            </div>

            <div class="card">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Lead</th>
                                <th>Customer</th>
                                <th>Executive</th>
                                <th>Lead status</th>
                                <th>AI verdict</th>
                                <th>Score</th>
                                <th>Updated</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($analyses as $a)
                                <tr>
                                    <td>#{{ $a->lead_id }}<br><small class="text-muted">{{ $a->service }}</small></td>
                                    <td>{{ $a->customer_name ?: '-' }}<br><small class="text-muted">{{ $a->contact_no }}</small></td>
                                    <td>{{ $a->executive_name ?: '-' }}</td>
                                    <td>{{ ucfirst((string) $a->lead_status) ?: '-' }}</td>
                                    <td style="max-width:380px;">
                                        @if ($a->health)<span class="badge bg-{{ $a->health_color }}">{{ $a->health_label }}</span>@endif
                                        @if ($a->isRunning())<span class="badge bg-info">Analyzing…</span>@elseif ($a->status === 'failed')<span class="badge bg-danger" title="{{ $a->error }}">Failed</span>@endif
                                        <div style="font-size:13px;">{{ \Illuminate\Support\Str::limit($a->result['headline'] ?? ($a->error ?? ''), 140) }}</div>
                                    </td>
                                    <td><strong>{{ $a->score ?? '—' }}</strong></td>
                                    <td><small>{{ optional($a->analyzed_at)->format('d M, h:i A') ?: '-' }}</small></td>
                                    <td><a href="{{ route($rp . '.ai_insights.show', [$type, $a->lead_id]) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No AI reviews yet. Open a lead and click <strong>Analyze with AI</strong>, or wait for the automatic review after new calls / chats.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            {{ $analyses->links() }}
        </div>
    </section>
</div>
@endsection
