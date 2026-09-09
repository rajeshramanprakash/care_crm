<style>
    .dr-detail {
        --dr-accent: #ea8a2b;
        --dr-accent-soft: rgba(234, 138, 43, 0.12);
        --dr-accent-border: rgba(234, 138, 43, 0.35);
        --dr-heading: #1a2340;
        --dr-muted: #6c757d;
        --dr-border: #e8ecf1;
        --dr-surface: #ffffff;
        --dr-bg: #f6f8fb;
        font-size: 0.9rem;
        color: #2c3e50;
    }
    .dr-detail * { box-sizing: border-box; }
    .dr-detail .dr-detail-card {
        border: none;
        border-radius: 0;
        box-shadow: none;
        margin-bottom: 0;
        background: transparent;
    }
    .dr-detail .dr-detail-card > .card-header {
        display: none;
    }
    .dr-detail .dr-detail-card > .card-body {
        padding: 0;
    }
    .dr-detail-hero {
        background: linear-gradient(135deg, #fff8f0 0%, #fff 48%, #f8fafc 100%);
        border-bottom: 1px solid var(--dr-border);
        padding: 1.25rem 1.35rem 1.1rem;
    }
    .dr-detail-hero-inner {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem 1.25rem;
        align-items: flex-start;
    }
    .dr-detail-avatar-wrap {
        flex-shrink: 0;
    }
    .dr-detail-avatar {
        width: 88px;
        height: 88px;
        border-radius: 16px;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.1);
        background: #f1f5f9;
    }
    .dr-detail-avatar--placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: var(--dr-accent);
        background: var(--dr-accent-soft);
    }
    .dr-detail-hero-main {
        flex: 1;
        min-width: 200px;
    }
    .dr-detail-hero-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--dr-heading);
        letter-spacing: -0.02em;
        margin: 0 0 0.35rem;
        line-height: 1.25;
    }
    .dr-detail-hero-sub {
        color: var(--dr-muted);
        font-size: 0.88rem;
        margin-bottom: 0.65rem;
    }
    .dr-detail-hero-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        align-items: center;
    }
    .dr-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.32em 0.85em;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .dr-status-pill--approved { background: #d4edda; color: #155724; }
    .dr-status-pill--rejected { background: #f8d7da; color: #721c24; }
    .dr-status-pill--pending { background: #fff3cd; color: #856404; }
    .dr-meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.28em 0.65em;
        background: #fff;
        border: 1px solid var(--dr-border);
        border-radius: 8px;
        font-size: 0.8rem;
        color: #495057;
    }
    .dr-meta-chip i { color: var(--dr-accent); opacity: 0.9; }
    .dr-detail-body {
        padding: 1.1rem 1.35rem 1.35rem;
        background: var(--dr-bg);
    }
    .dr-detail-body.dr-two-col-layout {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        align-items: start;
    }
    @media (max-width: 991.98px) {
        .dr-detail-body.dr-two-col-layout {
            grid-template-columns: 1fr;
        }
    }
    .dr-panel {
        background: var(--dr-surface);
        border: 1px solid var(--dr-border);
        border-radius: 14px;
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
    }
    .dr-two-col-layout .dr-panel {
        margin-bottom: 0;
    }
    .dr-two-col-layout .dr-panel--wide {
        grid-column: 1 / -1;
    }
    .dr-panel-head {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.75rem 1rem;
        background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
        border-bottom: 1px solid var(--dr-border);
    }
    .dr-panel-head i {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: var(--dr-accent-soft);
        color: var(--dr-accent);
        font-size: 0.8rem;
    }
    .dr-panel-head h6 {
        margin: 0;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--dr-heading);
    }
    .dr-panel-head .dr-panel-hint {
        margin-left: auto;
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--dr-muted);
        text-transform: none;
        letter-spacing: 0;
    }
    .dr-panel-body {
        padding: 0.85rem 1rem 1rem;
    }
    .dr-kv-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem 1rem;
        margin: 0;
    }
    .dr-kv-item {
        margin: 0;
        min-width: 0;
    }
    .dr-kv-item--full {
        grid-column: 1 / -1;
    }
    .dr-kv-item dt {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--dr-muted);
        margin: 0 0 0.2rem;
    }
    .dr-kv-item dd {
        margin: 0;
        font-size: 0.9rem;
        color: #2c3e50;
        word-break: break-word;
    }
    .dr-kv-item dd.dr-kv-empty {
        color: #adb5bd;
    }
    @media (max-width: 575.98px) {
        .dr-kv-grid { grid-template-columns: 1fr; }
    }
    .dr-chip {
        display: inline-block;
        padding: 0.22em 0.55em;
        margin: 0 0.25rem 0.25rem 0;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 600;
        background: var(--dr-accent-soft);
        color: var(--dr-heading);
        border: 1px solid var(--dr-accent-border);
    }
    .dr-chip--info {
        background: #e7f3ff;
        border-color: #b8daff;
        color: #004085;
    }
    .dr-chip--lang {
        background: #e8f4fd;
        border-color: #bee5eb;
        color: #0c5460;
    }
    .dr-timeline {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .dr-timeline li {
        position: relative;
        padding: 0 0 0.85rem 1.15rem;
        border-left: 2px solid var(--dr-border);
        margin-left: 0.35rem;
    }
    .dr-timeline li:last-child {
        padding-bottom: 0;
        border-left-color: transparent;
    }
    .dr-timeline li::before {
        content: '';
        position: absolute;
        left: -5px;
        top: 0.35rem;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--dr-accent);
        box-shadow: 0 0 0 3px var(--dr-accent-soft);
    }
    .dr-timeline-title {
        font-weight: 700;
        color: var(--dr-heading);
        font-size: 0.9rem;
    }
    .dr-timeline-meta {
        font-size: 0.82rem;
        color: var(--dr-muted);
    }
    .dr-timeline-detail {
        font-size: 0.82rem;
        color: #5a6c7d;
        margin-top: 0.25rem;
    }
    .dr-mode-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 0.65rem;
    }
    .dr-mode-card {
        border: 1px solid var(--dr-border);
        border-radius: 10px;
        padding: 0.65rem 0.75rem;
        background: #fafbfc;
    }
    .dr-mode-card.is-active {
        border-color: var(--dr-accent-border);
        background: var(--dr-accent-soft);
    }
    .dr-mode-card-title {
        font-weight: 700;
        font-size: 0.82rem;
        color: var(--dr-heading);
        margin-bottom: 0.35rem;
    }
    .dr-mode-card-title i {
        color: var(--dr-accent);
        margin-right: 0.25rem;
    }
    .dr-mode-card dl {
        margin: 0;
        font-size: 0.8rem;
    }
    .dr-mode-card dt {
        color: var(--dr-muted);
        font-weight: 600;
        font-size: 0.68rem;
        text-transform: uppercase;
        margin-top: 0.35rem;
    }
    .dr-mode-card dt:first-child { margin-top: 0; }
    .dr-mode-card dd {
        margin: 0.1rem 0 0;
        font-weight: 600;
        color: #2c3e50;
    }
    .dr-table-pro {
        width: 100%;
        font-size: 0.8rem;
        margin: 0;
    }
    .dr-table-pro thead th {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 700;
        color: var(--dr-muted);
        background: #fafbfc;
        border-bottom: 2px solid var(--dr-border) !important;
        white-space: nowrap;
        padding: 0.5rem 0.45rem !important;
    }
    .dr-table-pro tbody td {
        padding: 0.5rem 0.45rem !important;
        vertical-align: middle;
        border-color: #f0f2f4 !important;
    }
    .dr-table-wrap {
        border: 1px solid var(--dr-border);
        border-radius: 10px;
        overflow: auto;
        max-height: 320px;
    }
    .dr-doc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 0.75rem;
    }
    .dr-doc-card {
        border: 1px solid var(--dr-border);
        border-radius: 10px;
        padding: 0.65rem;
        background: #fafbfc;
    }
    .dr-doc-card-title {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--dr-muted);
        margin-bottom: 0.5rem;
    }
    .dr-doc-preview img {
        max-height: 200px;
        width: 100%;
        object-fit: contain;
        border-radius: 8px;
        background: #fff;
        border: 1px solid var(--dr-border);
    }
    .dr-doc-preview iframe {
        width: 100%;
        min-height: 220px;
        border-radius: 8px;
        border: 1px solid var(--dr-border);
    }
    .dr-inline-pricing-wrap {
        border: 1px solid var(--dr-accent-border) !important;
        border-radius: 12px !important;
        background: linear-gradient(180deg, #fffbf6 0%, #fff 100%) !important;
        padding: 1rem 1.1rem !important;
    }
    .dr-inline-pricing-wrap h6 {
        color: var(--dr-heading);
        font-weight: 700;
    }
    .dr-ref-comm-input-group .dr-ref-comm-type {
        max-width: 5.5rem;
        flex: 0 0 5.5rem;
        padding-left: 0.35rem;
        padding-right: 1.4rem;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .dr-ref-comm-input-group .dr-ref-comm-val {
        min-width: 0;
    }
    .dr-ref-comm-hint {
        font-size: 0.68rem;
        line-height: 1.25;
        display: block;
        margin-top: 0.2rem;
    }
    .dr-reg-profile-wrap {
        border-radius: 12px !important;
        border-color: var(--dr-border) !important;
    }
    .dr-reg-tags-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }
    .dr-reg-tag-pill {
        border: 1px solid var(--dr-border);
        background: #fff;
        color: var(--dr-heading);
        border-radius: 999px;
        padding: 0.35rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }
    .dr-reg-tag-pill:hover {
        border-color: var(--dr-accent-border);
        background: var(--dr-accent-soft);
    }
    .dr-reg-tag-pill.is-selected {
        background: linear-gradient(165deg, #f0a068 0%, var(--dr-accent) 100%);
        border-color: var(--dr-accent);
        color: #fff;
    }
    .dr-reg-modes-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }
    .dr-reg-mode-pill {
        border: 1px solid var(--dr-border);
        background: #fff;
        color: var(--dr-heading);
        border-radius: 999px;
        padding: 0.4rem 0.85rem;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }
    .dr-reg-mode-pill:hover {
        border-color: var(--dr-accent-border);
        background: var(--dr-accent-soft);
    }
    .dr-reg-mode-pill.is-selected {
        background: linear-gradient(165deg, #f0a068 0%, var(--dr-accent) 100%);
        border-color: var(--dr-accent);
        color: #fff;
    }
    .dr-reg-sub-block {
        border: 1px solid var(--dr-border);
        border-radius: 10px;
        background: #fff;
        padding: 0.85rem 1rem;
        margin-bottom: 0.65rem;
    }
    .dr-reg-sub-block-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.45rem;
    }
    .dr-reg-sub-block-head strong {
        font-size: 0.9rem;
        color: var(--dr-heading);
    }
    .dr-reg-sub-remove {
        border: none;
        background: transparent;
        color: #dc3545;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
    }
    .dr-reg-sub-remove:hover {
        text-decoration: underline;
    }
    .dr-reg-pricing-table th,
    .dr-reg-pricing-table td {
        vertical-align: middle;
        font-size: 0.82rem;
    }
    .dr-reg-pricing-table input {
        max-width: 140px;
    }
    .dr-reg-lang-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }
    .dr-reg-lang-pill {
        border: 1px solid var(--dr-border);
        background: #fff;
        color: var(--dr-heading);
        border-radius: 999px;
        padding: 0.35rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }
    .dr-reg-lang-pill:hover {
        border-color: #2d6a4f;
        background: #f0fdf4;
    }
    .dr-reg-lang-pill.is-selected {
        background: linear-gradient(165deg, #40916c 0%, #1a2e22 100%);
        border-color: #1a2e22;
        color: #fff;
    }
    .dr-reg-filters-wrap .dr-reg-filters-msg.text-success {
        color: #198754 !important;
    }
    .dr-empty {
        color: var(--dr-muted);
        font-size: 0.85rem;
        margin: 0;
    }
    .dr-leegality-panel {
        border: 1px solid #fdba74;
        border-radius: 12px;
        background: linear-gradient(180deg, #fff7ed 0%, #ffffff 55%);
        padding: 14px 16px;
    }
    .dr-leegality-head {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .dr-leegality-head-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        justify-content: flex-end;
    }
    .dr-leegality-title {
        font-weight: 700;
        color: #9a3412;
        font-size: 0.95rem;
    }
    .dr-leegality-sub {
        font-size: 0.8rem;
        color: #78716c;
        margin-top: 2px;
    }
    .dr-leegality-status-card {
        border: 1px solid #e7e5e4;
        border-radius: 10px;
        background: #fff;
        padding: 12px 14px;
    }
    .dr-leegality-status-card.status-signed,
    .dr-leegality-status-card.status-approved,
    .dr-leegality-status-card.status-completed {
        border-color: #86efac;
        background: #f0fdf4;
    }
    .dr-leegality-status-card.status-sent {
        border-color: #93c5fd;
        background: #eff6ff;
    }
    .dr-leegality-status-card.status-rejected,
    .dr-leegality-status-card.status-failed {
        border-color: #fca5a5;
        background: #fef2f2;
    }
    .dr-leegality-status-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }
    .dr-leegality-status-label { font-size: 0.78rem; color: #78716c; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
    .dr-leegality-status-value { font-size: 1.05rem; font-weight: 800; color: #1c1917; }
    .dr-leegality-meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 10px;
        margin-bottom: 10px;
    }
    .dr-leegality-meta-label { font-size: 0.72rem; color: #78716c; text-transform: uppercase; font-weight: 600; }
    .dr-leegality-meta-value { font-size: 0.9rem; font-weight: 600; color: #292524; word-break: break-word; }
    .dr-leegality-action-note { font-size: 0.82rem; color: #57534e; margin-bottom: 8px; }
    .dr-leegality-error { color: #b91c1c; font-size: 0.84rem; margin-bottom: 8px; }
    .dr-leegality-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .dr-leegality-empty {
        border: 1px dashed #fdba74;
        border-radius: 10px;
        padding: 12px;
        color: #78716c;
        font-size: 0.86rem;
        background: #fff;
    }
    #doctorViewModalBody .dr-detail-hero {
        border-radius: 0;
    }
    #doctorViewModalBody .dr-detail {
        border-radius: 0;
    }
</style>
