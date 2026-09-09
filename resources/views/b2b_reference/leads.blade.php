@extends('b2b_reference.layouts.app')
@section('page_title', 'Referred leads')

@push('styles')
<style>
    :root {
        --b2bref-primary: #f07f28;
        --b2bref-primary-dark: #cf6413;
        --b2bref-surface: #fff7ef;
        --b2bref-border: #e2e8f0;
    }
    .b2bref-leads {
        margin: -2px;
    }
    .b2bref-leads-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem 1rem;
        margin-bottom: 1rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid var(--b2bref-border);
    }
    .b2bref-leads-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 0.25rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .b2bref-leads-title i {
        color: var(--b2bref-primary);
        font-size: 1rem;
    }
    .b2bref-leads-caption {
        margin: 0;
        font-size: 0.8rem;
        color: #64748b;
        max-width: 36rem;
        line-height: 1.45;
    }
    .b2bref-pill-count {
        flex-shrink: 0;
        font-weight: 700;
        font-size: 0.8rem;
        padding: 0.4rem 0.75rem;
        border-radius: 999px;
        background: var(--b2bref-surface);
        color: var(--b2bref-primary-dark);
        border: 1px solid #ffd8b7;
    }
    .b2bref-toolbar {
        margin-bottom: 0.75rem;
    }
    .b2bref-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.85rem;
        pointer-events: none;
    }
    .b2bref-search-input {
        padding-left: 2rem;
        border-radius: 10px;
        height: 40px;
        border: 1px solid var(--b2bref-border);
    }
    .b2bref-search-input:focus {
        border-color: #fdba74;
        box-shadow: 0 0 0 3px rgba(240, 127, 40, 0.2);
    }
    .b2bref-search-meta {
        font-size: 0.78rem;
    }
    .b2bref-table-scroll {
        border-radius: 12px;
        border: 1px solid var(--b2bref-border);
        overflow: auto;
        -webkit-overflow-scrolling: touch;
        max-height: min(72vh, 640px);
        background: #fff;
    }
    .b2bref-table {
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.813rem;
    }
    .b2bref-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: linear-gradient(180deg, #fffaf5 0%, #fff3e8 100%);
        color: var(--b2bref-primary-dark);
        font-weight: 700;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
        border-bottom: 2px solid #ffd8b7 !important;
        border-top: none !important;
        vertical-align: middle;
        padding: 0.65rem 0.75rem !important;
    }
    .b2bref-table tbody td {
        padding: 0.55rem 0.75rem !important;
        vertical-align: middle;
        border-top: none !important;
        border-bottom: 1px solid #f1f5f9 !important;
        color: #334155;
    }
    .b2bref-table tbody tr:last-child td {
        border-bottom: none !important;
    }
    .b2bref-table tbody tr:hover td {
        background: #fafbfc;
    }
    .b2bref-lead-no {
        font-weight: 700;
        font-size: 0.75rem;
        padding: 0.28rem 0.5rem;
        border-radius: 8px;
        background: #fff3e8;
        color: var(--b2bref-primary-dark);
        border: 1px solid #ffd8b7;
        white-space: nowrap;
        display: inline-block;
    }
    .b2bref-strong {
        font-weight: 600;
        color: #0f172a;
    }
    .b2bref-muted-line {
        color: #64748b;
        font-size: 0.8rem;
        line-height: 1.35;
    }
</style>
@endpush

@section('content')
<div class="b2bref-leads">
    <div class="b2bref-leads-head">
        <div>
            <h2 class="b2bref-leads-title">
                <i class="fas fa-list-ul" aria-hidden="true"></i>
                Partners’ leads referred by you
            </h2>
            <p class="b2bref-leads-caption">Search across lead numbers, partner and client names, services, cities, amounts, status, and commissions.</p>
        </div>
        <span class="b2bref-pill-count">{{ count($tableRows) }} total</span>
    </div>

    <div class="b2bref-toolbar">
        <div class="position-relative">
            <i class="fas fa-search b2bref-search-icon" aria-hidden="true"></i>
            <input type="text" id="b2bRefLeadsSearch" class="form-control b2bref-search-input"
                   placeholder="Search…" autocomplete="off" aria-label="Filter referred leads">
        </div>
        <small class="text-muted b2bref-search-meta d-block mt-1" id="b2bRefSearchMeta">Showing {{ count($tableRows) }} of {{ count($tableRows) }}</small>
    </div>

    <div class="b2bref-table-scroll">
        <table class="table table-hover mb-0 b2bref-table">
            <thead>
                <tr>
                    <th scope="col">Lead no.</th>
                    <th scope="col">B2B partner</th>
                    <th scope="col">Client</th>
                    <th scope="col">Service</th>
                    <th scope="col">City</th>
                    <th scope="col">Price / date</th>
                    <th scope="col">Status</th>
                    <th scope="col">Deployment</th>
                    <th scope="col" class="text-right">Monthly ₹</th>
                    <th scope="col" class="text-right">Partner commission</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tableRows as $row)
                    <tr class="b2bref-row">
                        <td><span class="b2bref-lead-no">{{ $row['lead_no'] }}</span></td>
                        <td><span class="b2bref-strong">{{ $row['b2b_partner'] }}</span></td>
                        <td>{{ $row['client_name'] }}</td>
                        <td class="b2bref-muted-line">{{ $row['service'] }}</td>
                        <td>{{ $row['city'] }}</td>
                        <td class="b2bref-muted-line text-nowrap">{{ $row['price_date'] }}</td>
                        <td class="b2bref-muted-line" title="{{ e($row['status_with_remark']) }}">{{ \Illuminate\Support\Str::limit($row['status_with_remark'], 80) }}</td>
                        <td class="b2bref-muted-line small">{{ $row['from_to'] }}</td>
                        <td class="text-right text-nowrap font-weight-bold" style="font-variant-numeric: tabular-nums;">{{ $row['total_amount_monthly'] }}</td>
                        <td class="text-right text-nowrap font-weight-bold" style="font-variant-numeric: tabular-nums; color: var(--b2bref-primary-dark);">{{ $row['total_earnings_monthly'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">No leads yet for partners referred by you.</td>
                    </tr>
                @endforelse
                <tr id="b2bRefNoMatch" style="display:none;">
                    <td colspan="10" class="text-center py-4 text-muted">Nothing matches your search.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const inp = document.getElementById('b2bRefLeadsSearch');
    const meta = document.getElementById('b2bRefSearchMeta');
    const rows = Array.from(document.querySelectorAll('.b2bref-row'));
    const noMatch = document.getElementById('b2bRefNoMatch');
    const total = rows.length;
    if (!inp || !meta) return;

    function rowText(r) {
        return r.innerText.toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function run() {
        const q = (inp.value || '').trim().toLowerCase();
        let n = 0;
        rows.forEach(function (r) {
            const ok = q === '' || rowText(r).includes(q);
            r.style.display = ok ? '' : 'none';
            if (ok) n++;
        });
        if (noMatch) noMatch.style.display = total === 0 ? 'none' : (n === 0 ? '' : 'none');
        meta.textContent = total === 0 ? 'Showing 0 of 0' : ('Showing ' + n + ' of ' + total);
    }

    inp.addEventListener('input', run);
});
</script>
@endpush
