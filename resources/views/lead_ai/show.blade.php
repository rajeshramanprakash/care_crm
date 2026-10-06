@extends($layout)

@section('title', 'AI Lead Review')

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
            <div>
                <h1 class="m-0">{{ $lead->customer_name ?: 'Lead' }} <small class="text-muted">#{{ $lead->id }} · {{ ucfirst((string) $lead->status) }}</small></h1>
                <small class="text-muted">{{ $lead->contact_no }} · {{ $lead->query }}</small>
            </div>
            <div>
                <a href="{{ route($rp . '.ai_insights.index', ['type' => $type]) }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> AI Insights</a>
                <a href="{{ $leadUrl }}" class="btn btn-primary btn-sm">Open lead</a>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            @include('lead_ai._panel', ['aiType' => $type, 'aiLeadId' => $lead->id])
        </div>
    </section>
</div>
@endsection
