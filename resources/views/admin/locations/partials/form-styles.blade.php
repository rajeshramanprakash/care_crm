<style>
    .loc-page {
        --loc-accent: #fe992e;
        --loc-accent-dark: #e8892a;
        --loc-border: #e8ecf1;
        --loc-muted: #6c757d;
        --loc-heading: #1a2340;
        --loc-surface: #ffffff;
    }
    /* Admin layout: body overflow hidden — scroll this page */
    body.sidebar-mini.layout-fixed .wrapper .content-wrapper.loc-page {
        height: calc(100vh - 120px);
        max-height: calc(100vh - 120px);
        overflow-y: auto !important;
        overflow-x: hidden !important;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 1.5rem;
    }
    @media (max-width: 991.98px) {
        body.sidebar-mini.layout-fixed .wrapper .content-wrapper.loc-page {
            height: calc(100vh - 88px);
            max-height: calc(100vh - 88px);
        }
    }
    .loc-page .loc-hero {
        background: linear-gradient(135deg, var(--loc-accent) 0%, var(--loc-accent-dark) 100%);
        border-radius: 14px;
        color: #fff;
        padding: 1.15rem 1.35rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 28px rgba(254, 153, 46, 0.2);
    }
    .loc-page .loc-hero h1 {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0 0 0.25rem;
        color: #fff;
    }
    .loc-page .loc-hero p {
        margin: 0;
        font-size: 0.88rem;
        opacity: 0.92;
    }
    .loc-page .loc-breadcrumb {
        font-size: 0.8rem;
        margin-bottom: 0.5rem;
        opacity: 0.9;
    }
    .loc-page .loc-breadcrumb a {
        color: #fff;
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    .loc-page .loc-card {
        background: var(--loc-surface);
        border: 1px solid var(--loc-border);
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
        margin-bottom: 1.25rem;
        overflow: hidden;
    }
    .loc-page .loc-card-head {
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid var(--loc-border);
        background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--loc-heading);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .loc-page .loc-card-head i {
        color: var(--loc-accent);
        opacity: 0.95;
    }
    .loc-page .loc-card-body {
        padding: 1.15rem 1.25rem 1.25rem;
    }
    .loc-page .loc-label {
        font-weight: 600;
        font-size: 0.875rem;
        color: #374151;
        margin-bottom: 0.35rem;
    }
    .loc-page .loc-form-footer {
        position: sticky;
        bottom: 0;
        z-index: 20;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(8px);
        border: 1px solid var(--loc-border);
        border-radius: 12px;
        padding: 0.85rem 1.15rem;
        margin-top: 0.5rem;
        box-shadow: 0 -4px 24px rgba(15, 23, 42, 0.08);
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }
    .loc-page .btn-loc-primary {
        background: linear-gradient(135deg, var(--loc-accent) 0%, var(--loc-accent-dark) 100%);
        border: none;
        color: #fff;
        font-weight: 600;
        border-radius: 10px;
        padding: 0.5rem 1.25rem;
    }
    .loc-page .btn-loc-primary:hover {
        color: #fff;
        opacity: 0.95;
        box-shadow: 0 4px 14px rgba(254, 153, 46, 0.35);
    }
    .loc-page .loc-services-scroll {
        max-height: min(420px, 50vh);
        overflow-y: auto;
        overflow-x: hidden;
        border: 1px solid var(--loc-border);
        border-radius: 12px;
        padding: 0.85rem;
        background: #f8fafc;
        -webkit-overflow-scrolling: touch;
    }
    .loc-page .loc-service-row {
        background: #fff;
        border: 1px solid var(--loc-border) !important;
        border-radius: 12px !important;
        padding: 1rem !important;
        margin-bottom: 0.75rem !important;
    }
    .loc-page .loc-service-row:last-child {
        margin-bottom: 0 !important;
    }
    @media (max-width: 767.98px) {
        .loc-page .loc-service-row .row > [class*="col-"] {
            margin-bottom: 0.65rem;
        }
    }
    /* Pricing sections */
    .loc-page .loc-pricing-sections {
        border: none;
        border-radius: 0;
        padding: 0;
        background: transparent;
        margin-bottom: 0;
    }
    .loc-page .loc-pricing-sections h4 {
        font-weight: 700;
        font-size: 1rem;
        color: var(--loc-heading);
        margin-bottom: 0.75rem;
    }
    .loc-page .loc-pricing-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }
    .loc-page .loc-pricing-pill {
        border: none;
        border-radius: 999px;
        padding: 0.45rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        background: #eef2f6;
        color: #1e3a2f;
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .loc-page .loc-pricing-pill.active {
        background: #1e3a2f;
        color: #fff;
    }
    .loc-page .loc-pricing-hint {
        font-size: 0.82rem;
        color: var(--loc-muted);
        margin-bottom: 1rem;
        line-height: 1.45;
    }
    .loc-page .loc-pricing-panel { display: none; }
    .loc-page .loc-pricing-panel.active { display: block; }
    .loc-page .loc-doc-block {
        border: 1px solid var(--loc-border);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        background: #fafbfc;
    }
    .loc-page .loc-doc-block-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
    }
    .loc-page .loc-doc-block-title {
        font-weight: 700;
        color: var(--loc-heading);
        font-size: 0.95rem;
    }
    .loc-page .loc-mode-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin: 0.75rem 0;
    }
    .loc-page .loc-mode-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.85rem;
        border-radius: 999px;
        border: 1px solid #cfd8e3;
        background: #fff;
        font-size: 0.85rem;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        user-select: none;
    }
    .loc-page .loc-mode-chip.active {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }
    .loc-page .loc-mode-chip input { display: none; }
    .loc-page .loc-doc-modes-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin: 0 -0.15rem;
        padding: 0 0.15rem;
    }
    .loc-page .loc-doc-price-table {
        min-width: 640px;
        margin-bottom: 0;
    }
    .loc-page .loc-doc-price-table th {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--loc-muted);
        font-weight: 600;
        white-space: nowrap;
        background: #f1f5f9;
    }
    .loc-page .loc-doc-price-table td { vertical-align: middle; }
    .loc-page .loc-final-allowed {
        font-weight: 700;
        color: var(--loc-heading);
        white-space: nowrap;
    }
    .loc-page .loc-doc-rule {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 10px;
        padding: 0.65rem 0.85rem;
        font-size: 0.82rem;
        color: #9a3412;
        margin-top: 0.75rem;
        line-height: 1.45;
    }
    .loc-page #loc_doctor_pricing_blocks {
        max-height: none;
    }
</style>
