@php
    $corporatesByPartner = $items->mapWithKeys(function ($partner) use ($partner_type) {
        return [
            $partner->id => $partner->corporateUsers->map(fn ($c) => [
                'id' => $c->id,
                'corporate_name' => $c->corporate_name,
                'username' => $c->username,
                'is_active' => (bool) $c->is_active,
                'is_active_label' => $c->is_active ? 'Active' : 'Inactive',
                'created_at' => $c->created_at?->format('d M Y, h:i A') ?? '—',
                'login_url' => url('/corporate/login'),
                'employees_count' => $c->employees_count ?? 0,
                'employees_admin_url' => url('/admin/corporate-employees?corporate_id='.$c->id),
                'created_by' => $partner_type === 'broker'
                    ? ('Broker: ' . ($partner->company_name ?: $partner->name))
                    : ('Insurer: ' . ($partner->company_name ?: $partner->name)),
            ])->values()->all(),
        ];
    })->all();

    $partnersMeta = $items->mapWithKeys(fn ($p) => [
        $p->id => [
            'name' => $p->name,
            'company' => $p->company_name ?: '—',
            'username' => $p->username,
        ],
    ])->all();

    $modalPrefix = $partner_type . 'Corporate';
@endphp

{{-- List modal --}}
<div class="modal fade" id="{{ $modalPrefix }}ListModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalPrefix }}ListModalTitle">{{ $partner_label }} — Corporate accounts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="{{ $modalPrefix }}ListEmpty" class="p-4 text-muted d-none">No corporate accounts created yet.</div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0" id="{{ $modalPrefix }}ListTable">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Corporate name</th>
                                <th>Username</th>
                                <th>Status</th>
                                <th>Employees</th>
                                <th>Created</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="{{ $modalPrefix }}ListBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Detail modal --}}
<div class="modal fade" id="{{ $modalPrefix }}DetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Corporate account details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="text-muted" style="width:38%;">Corporate name</th>
                        <td id="{{ $modalPrefix }}DetailName">—</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Username</th>
                        <td><code id="{{ $modalPrefix }}DetailUsername">—</code></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Password</th>
                        <td id="{{ $modalPrefix }}DetailPassword" class="small text-muted">Set at creation (stored encrypted)</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Account status</th>
                        <td id="{{ $modalPrefix }}DetailStatus">—</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Created by</th>
                        <td id="{{ $modalPrefix }}DetailCreatedBy">—</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Created on</th>
                        <td id="{{ $modalPrefix }}DetailCreatedAt">—</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Login URL</th>
                        <td><a href="{{ url('/corporate/login') }}" target="_blank" rel="noopener" id="{{ $modalPrefix }}DetailLoginUrl">{{ url('/corporate/login') }}</a></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Employees added</th>
                        <td id="{{ $modalPrefix }}DetailEmployees">—</td>
                    </tr>
                </table>
                <p class="small text-muted mb-0 mt-2">
                    Fields above are what the {{ strtolower($partner_label) }} entered when creating this corporate login.
                    The password cannot be shown in plain text after it is saved.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        console.error('Bootstrap is not loaded; corporate account modals will not work.');
        return;
    }

    const corporatesByPartner = @json($corporatesByPartner);
    const partnersMeta = @json($partnersMeta);
    const partnerLabel = @json($partner_label);
    const prefix = @json($modalPrefix);

    const listModalEl = document.getElementById(prefix + 'ListModal');
    const detailModalEl = document.getElementById(prefix + 'DetailModal');
    if (!listModalEl || !detailModalEl) return;

    const listModal = bootstrap.Modal.getOrCreateInstance(listModalEl);
    const detailModal = bootstrap.Modal.getOrCreateInstance(detailModalEl);
    const listTitle = document.getElementById(prefix + 'ListModalTitle');
    const listBody = document.getElementById(prefix + 'ListBody');
    const listEmpty = document.getElementById(prefix + 'ListEmpty');
    const listTable = document.getElementById(prefix + 'ListTable');

    function openList(partnerId) {
        const meta = partnersMeta[partnerId] || {};
        const rows = corporatesByPartner[partnerId] || [];
        const titleName = meta.company && meta.company !== '—' ? meta.company : meta.name;
        listTitle.textContent = partnerLabel + ': ' + (titleName || 'Account') + ' — ' + rows.length + ' corporate ID(s)';

        listBody.innerHTML = '';
        if (rows.length === 0) {
            listEmpty.classList.remove('d-none');
            listTable.classList.add('d-none');
        } else {
            listEmpty.classList.add('d-none');
            listTable.classList.remove('d-none');
            rows.forEach(function (row, index) {
                const tr = document.createElement('tr');
                tr.style.cursor = 'pointer';
                const statusBadge = row.is_active
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Inactive</span>';
                tr.innerHTML =
                    '<td>' + (index + 1) + '</td>' +
                    '<td><strong>' + escapeHtml(row.corporate_name) + '</strong></td>' +
                    '<td><code>' + escapeHtml(row.username) + '</code></td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td class="text-center">' + (row.employees_count > 0
                        ? '<a href="' + escapeHtml(row.employees_admin_url) + '" class="btn btn-xs btn-outline-primary btn-sm" target="_blank">' + row.employees_count + '</a>'
                        : '0') + '</td>' +
                    '<td class="small">' + escapeHtml(row.created_at) + '</td>' +
                    '<td><button type="button" class="btn btn-xs btn-info btn-sm view-detail-btn">View</button></td>';
                tr.addEventListener('click', function (e) {
                    if (e.target.closest('.view-detail-btn') || e.target.tagName !== 'A') {
                        openDetail(row);
                    }
                });
                tr.querySelector('.view-detail-btn').addEventListener('click', function (e) {
                    e.stopPropagation();
                    openDetail(row);
                });
                listBody.appendChild(tr);
            });
        }
        listModal.show();
    }

    function openDetail(row) {
        document.getElementById(prefix + 'DetailName').textContent = row.corporate_name;
        document.getElementById(prefix + 'DetailUsername').textContent = row.username;
        document.getElementById(prefix + 'DetailStatus').innerHTML = row.is_active
            ? '<span class="badge badge-success">Active</span>'
            : '<span class="badge badge-secondary">Inactive</span>';
        document.getElementById(prefix + 'DetailCreatedBy').textContent = row.created_by;
        document.getElementById(prefix + 'DetailCreatedAt').textContent = row.created_at;
        var empEl = document.getElementById(prefix + 'DetailEmployees');
        if (row.employees_count > 0) {
            empEl.innerHTML = '<a href="' + escapeHtml(row.employees_admin_url) + '" target="_blank"><strong>' + row.employees_count + '</strong> — view list</a>';
        } else {
            empEl.textContent = '0 (none added yet)';
        }
        detailModal.show();
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    document.querySelectorAll('.js-partner-corporate-count[data-partner-type="{{ $partner_type }}"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openList(parseInt(btn.getAttribute('data-partner-id'), 10));
        });
    });
});
</script>
