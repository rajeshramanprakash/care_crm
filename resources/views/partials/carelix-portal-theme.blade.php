{{-- Carelix orange portal theme (registration / B2B / vendor style) --}}
<style>
    :root {
        --carelix-primary: #ea8a2b;
        --carelix-primary-dark: #cf6413;
        --carelix-primary-soft: #fff5eb;
        --carelix-bg: #f5f7fb;
        --carelix-text: #1f2937;
        --carelix-sidebar-text: #4b5563;
        --carelix-sidebar-icon: #9ca3af;
        --carelix-sidebar-active-bg: #fff5eb;
        --carelix-sidebar-active-border: #fed7aa;
    }
    html, body { font-family: 'Poppins', sans-serif; background: var(--carelix-bg); }
    .layout-fixed .main-sidebar.sidebar-dark-primary,
    .layout-fixed .main-sidebar.sidebar-dark-primary .sidebar {
        background: #fff !important;
        border-right: 1px solid #eceff4;
        box-shadow: 8px 0 26px rgba(15, 23, 42, 0.05);
    }
    .main-sidebar.sidebar-dark-primary .brand-link { color: #1f2937 !important; }
    .brand-link {
        border-bottom: 1px solid #eceff4 !important;
        padding-top: 0.9rem;
        padding-bottom: 0.9rem;
        background: linear-gradient(180deg, #ffffff 0%, #fffaf5 100%);
    }
    .brand-link .brand-image {
        float: none;
        max-height: 42px;
        margin: 0 auto;
        display: block;
    }
    .sidebar { padding-top: 0.3rem; }
    .sidebar .nav-link {
        border-radius: 10px;
        margin: 6px 10px;
        color: var(--carelix-sidebar-text) !important;
        font-weight: 600;
        width: auto;
        padding: 0.68rem 0.85rem;
        border: 1px solid transparent;
    }
    .sidebar .nav-link .nav-icon { color: var(--carelix-sidebar-icon) !important; font-size: 0.95rem; margin-right: 0.45rem; }
    .sidebar .nav-link:hover { background: #fff7ed; color: var(--carelix-primary) !important; border-color: #fed7aa; }
    .sidebar .nav-link:hover .nav-icon { color: var(--carelix-primary) !important; }
    .sidebar .nav-link.active {
        background: var(--carelix-sidebar-active-bg) !important;
        color: var(--carelix-primary) !important;
        box-shadow: 0 6px 14px rgba(234, 138, 43, 0.14);
        border: 1px solid var(--carelix-sidebar-active-border);
    }
    .sidebar .nav-link.active .nav-icon { color: var(--carelix-primary) !important; }
    .main-header.navbar .nav-link[data-widget="pushmenu"] { color: var(--carelix-primary) !important; }
    .main-header.navbar .nav-link[data-widget="pushmenu"]:hover { color: var(--carelix-primary-dark) !important; }
    .main-header.navbar { border-bottom: 1px solid #e5e7eb; background: #fff; }
    .carelix-portal-badge {
        border-radius: 9px;
        font-weight: 600;
        padding: 0.35rem 0.65rem;
        background: var(--carelix-primary-soft);
        color: var(--carelix-primary-dark);
        border: 1px solid #fed7aa;
        font-size: 0.82rem;
    }
    .content-wrapper { background: var(--carelix-bg); overflow-y: auto; max-height: calc(100vh - 57px); }
    .content-header h1 { color: var(--carelix-text); font-size: 1.3rem; font-weight: 700; margin: 0; }
    .carelix-content-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(17, 24, 39, 0.04);
        padding: 14px;
    }
    .card { border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 8px 24px rgba(17, 24, 39, 0.04); }
    .btn-primary { background: var(--carelix-primary); border-color: var(--carelix-primary); }
    .btn-primary:hover, .btn-primary:focus { background: var(--carelix-primary-dark); border-color: var(--carelix-primary-dark); }
    .btn-outline-primary {
        color: var(--carelix-primary-dark) !important;
        border-color: #fdba74 !important;
        background: #fff;
    }
    .btn-outline-primary:hover {
        background: var(--carelix-primary-soft) !important;
        border-color: var(--carelix-primary) !important;
        color: var(--carelix-primary-dark) !important;
    }
    .text-primary, a.text-primary { color: var(--carelix-primary-dark) !important; }
    .form-control:focus { border-color: var(--carelix-primary); box-shadow: 0 0 0 0.2rem rgba(234, 138, 43, 0.2); }
    html { overflow-x: hidden; }
    .wrapper { overflow-x: hidden; }
</style>
