@php
    $remark = is_array($remark ?? null) ? $remark : [];
    $text = $remark['remark'] ?? '';
    $original = $remark['original_remark'] ?? '';
    $isAi = !empty($remark['is_ai_polished']);
    $name = $remark['created_by_name'] ?? 'Unknown';
    $at = $remark['created_at'] ?? '—';
    $status = $remark['status_at_remark'] ?? '';
    $parts = preg_split('/\s+/', trim($name), 2);
    $initials = strtoupper(mb_substr($parts[0] ?? 'U', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
    if ($initials === '') {
        $initials = 'U';
    }
    $compact = $compact ?? false;
@endphp
<div class="clsr-timeline-item{{ $compact ? ' clsr-timeline-item--compact' : '' }}">
    <div class="clsr-timeline-marker" aria-hidden="true"></div>
    <article class="clsr-remark-card{{ $compact ? ' clsr-remark-card--compact' : '' }}">
        <header class="clsr-remark-card__head">
            <div class="clsr-remark-card__who">
                <span class="clsr-avatar" title="{{ $name }}">{{ $initials }}</span>
                <div class="clsr-who-text">
                    <span class="clsr-name">{{ $name }}</span>
                    <time class="clsr-date">{{ $at }}</time>
                </div>
            </div>
            @if($status !== '')
                <span class="clsr-status-chip">{{ $status }}</span>
            @endif
        </header>
        <div class="clsr-remark-card__body">
            @if($isAi && $original !== '')
                <div class="clsr-remark-blocks">
                    <div class="clsr-remark-block clsr-remark-block--english">
                        <span class="clsr-remark-block__label"><i class="fas fa-language"></i> English</span>
                        <p class="clsr-remark-text">{{ $text }}</p>
                    </div>
                    <div class="clsr-remark-block clsr-remark-block--draft">
                        <span class="clsr-remark-block__label"><i class="fas fa-pen-fancy"></i> Draft notes</span>
                        <p class="clsr-remark-text clsr-remark-text--draft">{{ $original }}</p>
                    </div>
                </div>
            @else
                <p class="clsr-remark-text">{{ $text }}</p>
            @endif
        </div>
    </article>
</div>
