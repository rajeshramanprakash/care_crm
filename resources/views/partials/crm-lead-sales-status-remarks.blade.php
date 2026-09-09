@php
    $remarks = $remarks ?? [];
    $readOnly = $readOnly ?? true;
    $panelTitle = $panelTitle ?? 'Sales / Manager Status Updates';
    $sidebar = $sidebar ?? false;
    $inColumn = $inColumn ?? false;
    $fullWidth = $fullWidth ?? true;
@endphp
@if(count($remarks) > 0)
@include('includes.lead-status-remarks-assets', ['part' => 'css'])
@if($sidebar)
    @if(!$inColumn)<div class="clsr-inline-wrap">@endif
    <section class="clsr-panel clsr-panel--sidebar" aria-label="{{ $panelTitle }}">
        <header class="clsr-panel__head">
            <h4 class="clsr-panel__title">
                <i class="fas fa-history" aria-hidden="true"></i>{{ $panelTitle }}
            </h4>
            <div class="clsr-panel__meta">
                <span class="clsr-pill clsr-pill--count">{{ count($remarks) }}</span>
                @if($readOnly)
                    <span class="clsr-pill">Read only</span>
                @endif
            </div>
        </header>
        <div class="clsr-panel__body clsr-panel__body--scroll">
            <div class="clsr-timeline">
                @foreach($remarks as $r)
                    @include('partials.crm-lead-sales-status-remark-item', ['remark' => $r, 'compact' => true])
                @endforeach
            </div>
        </div>
    </section>
    @if(!$inColumn)</div>@endif
@else
<div class="{{ $fullWidth ? 'col-12 mt-3 mb-2' : 'mt-2 clsr-inline-wrap' }}">
    <section class="clsr-panel {{ $fullWidth ? '' : 'clsr-panel--inline' }}" aria-label="{{ $panelTitle }}">
        <header class="clsr-panel__head">
            <h4 class="clsr-panel__title">
                <i class="fas fa-history" aria-hidden="true"></i>{{ $panelTitle }}
            </h4>
            <div class="clsr-panel__meta">
                <span class="clsr-pill clsr-pill--count">{{ count($remarks) }} {{ count($remarks) === 1 ? 'update' : 'updates' }}</span>
                @if($readOnly)
                    <span class="clsr-pill">Read only</span>
                @endif
            </div>
        </header>
        <div class="clsr-panel__body clsr-panel__body--scroll">
            <div class="clsr-timeline">
                @foreach($remarks as $r)
                    @include('partials.crm-lead-sales-status-remark-item', ['remark' => $r])
                @endforeach
            </div>
        </div>
    </section>
</div>
@endif
@endif
