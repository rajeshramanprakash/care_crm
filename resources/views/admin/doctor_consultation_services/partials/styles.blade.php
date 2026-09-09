<style>
    .dcs-page {
        --dcs-accent: #fe992e;
        --dcs-accent-dark: #e8892a;
        --dcs-surface: #ffffff;
        --dcs-border: #e8ecf1;
        --dcs-muted: #6c757d;
        --dcs-heading: #1a2340;
        --dcs-bg: #f4f6f9;
    }
    /* Admin layout sets body overflow:hidden — allow scroll on this page */
    body.sidebar-mini.layout-fixed .wrapper .content-wrapper.dcs-page {
        max-height: calc(100vh - 3.5rem);
        max-height: calc(100dvh - 3.5rem);
        overflow-y: auto !important;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
    }
    .dcs-page .dcs-form-wrap {
        max-width: 820px;
    }
    .dcs-page .dcs-sub-services-panel .dcs-panel-body {
        padding-top: 0.5rem;
    }
    .dcs-page .dcs-sub-service-card {
        border: 1px solid var(--dcs-border);
        border-radius: 12px;
        background: #fafbfc;
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .dcs-page .dcs-sub-service-card:last-child {
        margin-bottom: 0;
    }
    .dcs-page .dcs-sub-service-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.65rem 1rem;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid var(--dcs-border);
    }
    .dcs-page .dcs-sub-service-title {
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--dcs-heading);
    }
    .dcs-page .dcs-sub-service-body {
        padding: 1rem 1.1rem 1.1rem;
        background: #fff;
    }
    .dcs-page .dcs-sub-deleted {
        display: none !important;
    }
    .dcs-page #dcs_main_tags_group.dcs-tags-disabled .dcs-tags-box {
        opacity: 0.55;
        pointer-events: none;
        background: #f1f3f5;
        border-style: dashed;
    }
    .dcs-page #dcs_main_tags_group.dcs-tags-disabled .dcs-label {
        color: var(--dcs-muted);
    }
    .dcs-page .dcs-tags-box {
        border: 1px solid var(--dcs-border);
        border-radius: 10px;
        padding: 0.65rem 0.75rem;
        background: #fff;
        min-height: 48px;
    }
    .dcs-page .dcs-tags-box:focus-within {
        border-color: var(--dcs-accent);
        box-shadow: 0 0 0 0.2rem rgba(254, 153, 46, 0.18);
    }
    .dcs-page .dcs-tags-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 0.5rem;
    }
    .dcs-page .dcs-tags-list:empty {
        margin-bottom: 0;
    }
    .dcs-page .dcs-tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.55rem;
        background: rgba(254, 153, 46, 0.12);
        color: var(--dcs-heading);
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 600;
        border: 1px solid rgba(254, 153, 46, 0.35);
    }
    .dcs-page .dcs-tag-chip button {
        border: none;
        background: transparent;
        color: #c45c00;
        font-size: 1rem;
        line-height: 1;
        padding: 0;
        cursor: pointer;
        opacity: 0.85;
    }
    .dcs-page .dcs-tag-chip button:hover {
        opacity: 1;
    }
    .dcs-page .dcs-tags-input {
        border: none;
        outline: none;
        width: 100%;
        font-size: 0.9rem;
        padding: 0.15rem 0.1rem;
        background: transparent;
    }
    .dcs-page .dcs-tags-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.35rem;
    }
    .dcs-page .dcs-tag-preview {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        background: #f1f3f6;
        color: var(--dcs-muted);
    }
    .dcs-page .dcs-hero {
        background: linear-gradient(135deg, var(--dcs-accent) 0%, var(--dcs-accent-dark) 100%);
        border-radius: 14px;
        color: #fff;
        padding: 1.35rem 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 28px rgba(254, 153, 46, 0.22);
    }
    .dcs-page .dcs-hero h1 {
        font-size: 1.35rem;
        font-weight: 700;
        margin: 0 0 0.35rem;
        color: #fff;
    }
    .dcs-page .dcs-hero p {
        margin: 0;
        font-size: 0.9rem;
        opacity: 0.92;
        max-width: 640px;
    }
    .dcs-page .dcs-breadcrumb {
        font-size: 0.8rem;
        margin-bottom: 0.65rem;
        opacity: 0.9;
    }
    .dcs-page .dcs-breadcrumb a {
        color: #fff;
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    .dcs-page .dcs-breadcrumb a:hover {
        color: #fff;
        opacity: 0.85;
    }
    .dcs-page .dcs-panel {
        background: var(--dcs-surface);
        border: 1px solid var(--dcs-border);
        border-radius: 14px;
        box-shadow: 0 4px 22px rgba(26, 35, 64, 0.06);
        margin-bottom: 1.25rem;
        overflow: hidden;
    }
    .dcs-page .dcs-panel-head {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--dcs-border);
        background: #fafbfc;
    }
    .dcs-page .dcs-panel-head h2 {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--dcs-heading);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .dcs-page .dcs-panel-head h2 i {
        color: var(--dcs-accent-dark);
        font-size: 1rem;
    }
    .dcs-page .dcs-panel-body {
        padding: 1.25rem;
    }
    .dcs-page .dcs-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--dcs-heading);
        margin-bottom: 0.35rem;
        display: block;
    }
    .dcs-page .dcs-label .req {
        color: #dc3545;
    }
    .dcs-page .dcs-hint {
        font-size: 0.78rem;
        color: var(--dcs-muted);
        margin-top: 0.35rem;
        line-height: 1.4;
    }
    .dcs-page .form-control {
        border-radius: 10px;
        border-color: var(--dcs-border);
        font-size: 0.9rem;
        padding: 0.55rem 0.85rem;
    }
    .dcs-page .form-control:focus {
        border-color: var(--dcs-accent);
        box-shadow: 0 0 0 0.2rem rgba(254, 153, 46, 0.18);
    }
    .dcs-page .dcs-duration-wrap {
        display: flex;
        align-items: stretch;
        gap: 0;
        max-width: 220px;
    }
    .dcs-page .dcs-duration-wrap .form-control {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-right: 0;
    }
    .dcs-page .dcs-duration-suffix {
        display: flex;
        align-items: center;
        padding: 0 0.85rem;
        background: #f1f3f6;
        border: 1px solid var(--dcs-border);
        border-left: 0;
        border-radius: 0 10px 10px 0;
        font-size: 0.85rem;
        color: var(--dcs-muted);
        font-weight: 500;
    }
    .dcs-page .dcs-active-card {
        background: linear-gradient(180deg, #fffbf6 0%, #fff 100%);
        border: 1px solid rgba(254, 153, 46, 0.25);
        border-radius: 12px;
        padding: 1rem 1.1rem;
    }
    .dcs-page .custom-switch .custom-control-label::before {
        border-radius: 1rem;
        height: 1.35rem;
        width: 2.5rem;
    }
    .dcs-page .custom-switch .custom-control-input:checked ~ .custom-control-label::before {
        background-color: var(--dcs-accent);
        border-color: var(--dcs-accent);
    }
    .dcs-page .custom-switch .custom-control-label::after {
        height: calc(1.35rem - 4px);
        width: calc(1.35rem - 4px);
    }
    .dcs-page .dcs-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem;
        padding: 1rem 1.25rem;
        background: #fafbfc;
        border-top: 1px solid var(--dcs-border);
        border-radius: 0 0 14px 14px;
    }
    .dcs-page .btn-dcs-primary {
        background: linear-gradient(135deg, var(--dcs-accent) 0%, var(--dcs-accent-dark) 100%);
        border: none;
        color: #fff;
        font-weight: 600;
        padding: 0.55rem 1.35rem;
        border-radius: 10px;
        box-shadow: 0 4px 14px rgba(254, 153, 46, 0.3);
    }
    .dcs-page .btn-dcs-primary:hover {
        color: #fff;
        filter: brightness(1.03);
        box-shadow: 0 6px 18px rgba(254, 153, 46, 0.35);
    }
    .dcs-page .btn-dcs-ghost {
        border-radius: 10px;
        font-weight: 600;
        padding: 0.55rem 1.15rem;
        border: 1px solid var(--dcs-border);
        color: var(--dcs-heading);
        background: #fff;
    }
    .dcs-page .btn-dcs-ghost:hover {
        background: #f8f9fb;
        color: var(--dcs-heading);
    }
    .dcs-page .dcs-table-card {
        border-radius: 14px;
        border: 1px solid var(--dcs-border);
        box-shadow: 0 4px 22px rgba(26, 35, 64, 0.06);
        overflow: hidden;
    }
    .dcs-page .dcs-table-card .card-header {
        background: linear-gradient(135deg, var(--dcs-accent) 0%, var(--dcs-accent-dark) 100%);
        color: #fff;
        border: none;
        padding: 1rem 1.25rem;
    }
    .dcs-page .dcs-table-card .card-header .card-title {
        color: #fff;
        font-weight: 700;
        font-size: 1.1rem;
        margin: 0;
    }
    .dcs-page .dcs-table-card .btn-light {
        font-weight: 600;
        border-radius: 10px;
        color: var(--dcs-heading);
    }
    .dcs-page .table thead th {
        background: #f8f9fb;
        border-bottom: 2px solid var(--dcs-border);
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--dcs-muted);
        font-weight: 700;
    }
    .dcs-page .badge-dcs-active {
        background: rgba(40, 167, 69, 0.12);
        color: #1e7e34;
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 6px;
    }
    .dcs-page .badge-dcs-inactive {
        background: rgba(108, 117, 125, 0.12);
        color: #5a6268;
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 6px;
    }
    .dcs-page .dcs-empty {
        padding: 3rem 1.5rem;
        text-align: center;
        color: var(--dcs-muted);
    }
    .dcs-page .dcs-empty i {
        font-size: 2.5rem;
        color: var(--dcs-border);
        margin-bottom: 0.75rem;
    }
</style>
