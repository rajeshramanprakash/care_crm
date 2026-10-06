@extends($layout)

@section('title', 'AI Lead Insights')

@section('main')
<style>
    .ai-ins .ai-tabs .btn{border-radius:20px;padding:4px 16px;font-weight:600}
    .ai-ins .ai-filter{display:flex;flex-wrap:wrap;align-items:center;gap:6px}
    .ai-ins .ai-filter .btn{border-radius:16px;font-size:12.5px;padding:3px 12px}
    .ai-ins .ai-search{display:flex;gap:6px;margin-left:auto;min-width:280px}
    .ai-ins .ai-search .form-control{height:31px}
    .ai-ins .ai-table{min-width:1100px;margin-bottom:0}
    .ai-ins .ai-table thead th{background:#f4f2fb;color:#4b2bbf;font-size:12.5px;text-transform:uppercase;letter-spacing:.3px;border-bottom:2px solid #e3dcff;white-space:nowrap;vertical-align:middle}
    .ai-ins .ai-table td{vertical-align:middle;font-size:13.5px}
    .ai-ins .ai-table .sub{display:block;color:#6c757d;font-size:12px;margin-top:2px}
    .ai-ins .ai-headline{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:13px;margin-top:4px;color:#343a40}
    .ai-ins .ai-score{font-weight:700;font-size:15px}
    .ai-ins .ai-score .progress{height:5px;width:64px;margin-top:4px}
    .ai-ins .ai-badge{font-size:11.5px;padding:4px 9px;border-radius:10px;font-weight:600}
    .ai-ins .ai-foot{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;padding:12px 16px;border-top:1px solid #eee}
    .ai-ins .ai-foot .pagination{margin:0}
    .ai-ins .ai-foot nav{margin-left:auto}
    .ai-ins .ai-foot .page-link{padding:4px 11px;font-size:13px}
</style>

<div class="content-wrapper ai-ins">
    <section class="content-header">
        <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between" style="gap:10px;">
            <div>
                <h1 class="m-0"><i class="fas fa-robot text-primary"></i> AI Lead Insights</h1>
                <small class="text-muted">AI review of each lead from calls, recordings, WhatsApp chat and remarks. Visible only to Admin.</small>
            </div>
            @if (count($types) > 1)
                <div class="ai-tabs btn-group">
                    @foreach ($types as $t)
                        <a href="{{ route($rp . '.ai_insights.index', ['type' => $t]) }}"
                           class="btn btn-sm {{ $type === $t ? 'btn-primary' : 'btn-outline-primary' }}">{{ \App\Models\LeadAiAnalysis::TYPES[$t] }} leads</a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if (! $configured)
                <div class="alert alert-warning">AI is not configured on this server. Add <code>GEMINI_API_KEY</code> to the server <code>.env</code>.</div>
            @endif

            <div class="card">
                <div class="card-header bg-white">
                    <div class="ai-filter">
                        <a href="{{ route($rp . '.ai_insights.index', array_filter(['type' => $type, 'q' => request('q')])) }}"
                           class="btn {{ request('health') ? 'btn-outline-secondary' : 'btn-secondary' }}">All <strong>{{ $healthCounts->sum() }}</strong></a>
                        @foreach (\App\Models\LeadAiAnalysis::HEALTH as $key => $label)
                            @php $c = \App\Models\LeadAiAnalysis::HEALTH_COLORS[$key]; @endphp
                            <a href="{{ route($rp . '.ai_insights.index', array_filter(['type' => $type, 'health' => $key, 'q' => request('q')])) }}"
                               class="btn {{ request('health') === $key ? 'btn-' . $c : 'btn-outline-' . $c }}">{{ $label }} <strong>{{ $healthCounts[$key] ?? 0 }}</strong></a>
                        @endforeach

                        <form method="GET" class="ai-search">
                            <input type="hidden" name="type" value="{{ $type }}">
                            @if (request('health'))<input type="hidden" name="health" value="{{ request('health') }}">@endif
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Name, mobile or lead no.">
                            <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                            @if (request('q'))
                                <a href="{{ route($rp . '.ai_insights.index', array_filter(['type' => $type, 'health' => request('health')])) }}" class="btn btn-sm btn-light" title="Clear"><i class="fas fa-times"></i></a>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover ai-table">
                        <thead>
                            <tr>
                                <th style="width:110px;">Lead</th>
                                <th style="width:190px;">Customer</th>
                                <th style="width:140px;">Executive</th>
                                <th style="width:130px;">Lead status</th>
                                <th style="min-width:300px;">AI verdict</th>
                                <th style="width:90px;">Score</th>
                                <th style="width:120px;">Updated</th>
                                <th style="width:80px;" class="text-right"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($analyses as $a)
                                @php
                                    $score = $a->score;
                                    $scoreColor = $score === null ? 'secondary' : ($score >= 70 ? 'success' : ($score >= 40 ? 'warning' : 'danger'));
                                @endphp
                                <tr>
                                    <td><strong>#{{ $a->lead_id }}</strong><span class="sub">{{ \Illuminate\Support\Str::limit((string) $a->service, 24) ?: '-' }}</span></td>
                                    <td>{{ $a->customer_name ?: '-' }}<span class="sub">{{ $a->contact_no ?: '' }}</span></td>
                                    <td>{{ $a->executive_name ?: '-' }}</td>
                                    <td><span class="badge badge-light border ai-badge">{{ ucfirst((string) $a->lead_status) ?: '-' }}</span></td>
                                    <td>
                                        @if ($a->health)<span class="badge bg-{{ $a->health_color }} ai-badge">{{ $a->health_label }}</span>@endif
                                        @if ($a->isRunning())
                                            <span class="badge bg-info ai-badge"><i class="fas fa-spinner fa-spin"></i> Analyzing</span>
                                        @elseif ($a->status === 'failed')
                                            <span class="badge bg-danger ai-badge" title="{{ $a->error }}">Failed</span>
                                        @endif
                                        <div class="ai-headline" title="{{ $a->result['headline'] ?? $a->error }}">{{ $a->result['headline'] ?? ($a->error ?? '') }}</div>
                                    </td>
                                    <td>
                                        <div class="ai-score text-{{ $scoreColor }}">{{ $score ?? '—' }}
                                            @if ($score !== null)
                                                <div class="progress"><div class="progress-bar bg-{{ $scoreColor }}" style="width:{{ max(0, min(100, (int) $score)) }}%"></div></div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ optional($a->analyzed_at)->format('d M Y') ?: '-' }}<span class="sub">{{ optional($a->analyzed_at)->format('h:i A') }}</span></td>
                                    <td class="text-right"><a href="{{ route($rp . '.ai_insights.show', [$type, $a->lead_id]) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-robot fa-2x mb-2 d-block" style="opacity:.4"></i>
                                    No AI reviews {{ request('q') || request('health') ? 'match this filter' : 'yet' }}. Reviews are created automatically after new calls, chats and updates.
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($analyses->total() > 0)
                    <div class="ai-foot">
                        <small class="text-muted">Showing {{ $analyses->firstItem() }}–{{ $analyses->lastItem() }} of {{ $analyses->total() }} reviews</small>
                        {{ $analyses->onEachSide(1)->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
