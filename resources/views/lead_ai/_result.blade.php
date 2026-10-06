@php
    $r = $analysis?->result ?? [];
    $isOp = $type === 'operation';
    $src = $analysis?->sources ?? [];
    $sections = [
        'what_went_well' => ['What went well', 'fa-thumbs-up', 'text-success'],
        'mistakes' => [$isOp ? 'Gaps / mistakes in operation' : 'Mistakes by sales', 'fa-exclamation-triangle', 'text-danger'],
        'how_to_improve' => [$isOp ? 'How operation can do better' : 'How this deal could be handled better', 'fa-chart-line', 'text-primary'],
        'communication_tips' => ['Way of talking — tips', 'fa-comments', 'text-info'],
        'next_actions' => ['Next actions', 'fa-tasks', 'text-warning'],
    ];
@endphp

@if (! $configured)
    <div class="alert alert-warning mb-0">AI is not configured on this server. Add <code>GEMINI_API_KEY</code> to the server <code>.env</code>.</div>
@elseif (! $analysis || ($analysis->status !== 'done' && empty($r)))
    @if ($analysis && $analysis->isRunning())
        <div class="text-muted"><i class="fas fa-spinner fa-spin"></i> AI is reading calls, recordings, WhatsApp chat and remarks… this can take 1–2 minutes.</div>
    @elseif ($analysis && $analysis->status === 'failed')
        <div class="alert alert-danger mb-0"><strong>AI analysis failed.</strong> {{ $analysis->error }}</div>
    @else
        <div class="text-muted">No AI review yet. Click <strong>Analyze with AI</strong> — it reads every call (answered / missed), call recordings, WhatsApp chat and remarks of this lead and tells what went right, what went wrong and how to do better.</div>
    @endif
@else
    <div data-ai-has-result="1">
        @if ($analysis->isRunning())
            <div class="alert alert-info py-2"><i class="fas fa-spinner fa-spin"></i> Updating with the latest activity… (showing the previous review)</div>
        @elseif ($analysis->status === 'failed')
            <div class="alert alert-warning py-2">Last update failed: {{ $analysis->error }} (showing the previous review)</div>
        @endif

        <div class="d-flex align-items-center flex-wrap" style="gap:16px;">
            <div class="text-center">
                <div class="ai-score text-{{ $analysis->health_color }}">{{ $analysis->score ?? '—' }}</div>
                <small class="text-muted">score / 100</small>
            </div>
            <div style="flex:1;min-width:220px;">
                <span class="ai-chip bg-{{ $analysis->health_color }} text-white">{{ $analysis->health_label }}</span>
                @if (! empty($r['customer_sentiment']))<span class="ai-chip" style="background:#eee;">Customer: {{ ucfirst($r['customer_sentiment']) }}</span>@endif
                <div class="mt-1" style="font-size:16px;font-weight:600;">{{ $r['headline'] ?? '' }}</div>
            </div>
        </div>

        <div class="ai-sec"><div class="ai-box">{{ $r['summary'] ?? '' }}</div></div>

        @if (! empty($r['outcome_reason']))
            <div class="ai-sec">
                <h6><i class="fas fa-search"></i> {{ $isOp ? 'Root cause / current situation' : 'Why it closed / did not close' }}</h6>
                <div>{{ $r['outcome_reason'] }}</div>
            </div>
        @endif

        <div class="row">
            @foreach ($sections as $key => [$label, $icon, $color])
                @if (! empty($r[$key]))
                    <div class="col-md-6 ai-sec">
                        <h6><i class="fas {{ $icon }} {{ $color }}"></i> {{ $label }}</h6>
                        <ul>@foreach ($r[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
                    </div>
                @endif
            @endforeach
        </div>

        @if (! empty($r['suggested_message']))
            <div class="ai-sec">
                <h6><i class="fab fa-whatsapp text-success"></i> Suggested message / script for next contact</h6>
                <div class="ai-box">{{ $r['suggested_message'] }}</div>
            </div>
        @endif

        <div class="row">
            @if (! empty($r['call_review']))
                <div class="col-md-6 ai-sec"><h6><i class="fas fa-phone-alt"></i> Calls &amp; recordings</h6><div>{{ $r['call_review'] }}</div></div>
            @endif
            @if (! empty($r['whatsapp_review']))
                <div class="col-md-6 ai-sec"><h6><i class="fab fa-whatsapp"></i> WhatsApp chat</h6><div>{{ $r['whatsapp_review'] }}</div></div>
            @endif
        </div>

        @if (! empty($r['journey']))
            <div class="ai-sec">
                <h6><i class="fas fa-route"></i> Lead journey</h6>
                <ul>@foreach ($r['journey'] as $step)<li>{{ $step }}</li>@endforeach</ul>
            </div>
        @endif

        @if (! empty($r['missing_data']))
            <div class="ai-sec text-muted"><small><strong>Data missing:</strong> {{ implode(' · ', $r['missing_data']) }}</small></div>
        @endif

        <div class="mt-3 text-muted" style="font-size:12px;">
            Read by AI: {{ $src['calls'] ?? 0 }} calls ({{ $src['answered'] ?? 0 }} answered, {{ $src['missed'] ?? 0 }} missed/busy),
            {{ $src['transcribed'] ?? 0 }} recordings heard, {{ $src['whatsapp'] ?? 0 }} WhatsApp messages, {{ $src['remarks'] ?? 0 }} remarks
            · Updated {{ optional($analysis->analyzed_at)->format('d M Y, h:i A') }}
            @if ($analysis->model) · {{ $analysis->model }} @endif
        </div>
    </div>
@endif
