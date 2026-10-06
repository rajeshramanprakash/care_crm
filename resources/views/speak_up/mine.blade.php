@extends($submitter['layout'])
@section('title', 'Speak Up — My Submissions')

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    @include('speak_up._shell_top')
                    <div class="card su-card">
                        <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-list mr-2 me-2"></i>My Submissions (with my name)</h3></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Reference</th><th>Category</th><th>Subject</th><th>Status</th><th>Date</th><th></th></tr></thead>
                                    <tbody>
                                    @forelse ($submissions as $submission)
                                        <tr>
                                            <td class="text-nowrap">{{ $submission->reference_no }} @if($submission->has_unread_reply)<span class="badge badge-danger bg-danger">New reply</span>@endif</td>
                                            <td>{{ $submission->category_label }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($submission->subject, 50) }}</td>
                                            <td>@include('speak_up._status_badge')</td>
                                            <td class="text-nowrap">{{ $submission->created_at->format('d M Y') }}</td>
                                            <td><a href="{{ route('speak_up.mine.show', $submission->id) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">No named submissions.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{ $submissions->links('pagination::bootstrap-4') }}
                        </div>
                    </div>

                    <div class="card su-card mt-3">
                        <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-user-secret mr-2 me-2"></i>Anonymous — saved on this device</h3></div>
                        <div class="card-body">
                            <p class="small text-muted">Anonymous submissions are not linked to your account. They are remembered only in this browser, so they show here only on the phone / computer you sent them from. Elsewhere, use <a href="{{ route('speak_up.status') }}">Check Status</a> with the reference number and follow-up key.</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Reference</th><th>Category</th><th>Subject</th><th>Status</th><th>Date</th><th></th></tr></thead>
                                    <tbody id="suDeviceRows"><tr><td colspan="6" class="text-center text-muted py-4">Loading…</td></tr></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@include('speak_up._device_store')
<script>
    (function () {
        var rows = document.getElementById('suDeviceRows');
        var csrf = @json(csrf_token());
        var checkUrl = @json(route('speak_up.status.check'));
        var badge = { new: 'primary', under_review: 'warning', resolved: 'success' };
        function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
        function message(text) { rows.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">' + esc(text) + '</td></tr>'; }

        var saved = window.SpeakUpDevice.list();
        if (!saved.length) { message('No anonymous submission saved on this device.'); return; }
        var keys = {};
        saved.forEach(function (i) { keys[i.reference_no] = i.follow_up_key; });

        fetch(@json(route('speak_up.saved')), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ items: saved.map(function (i) { return { reference_no: i.reference_no, follow_up_key: i.follow_up_key }; }) })
        }).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); }).then(function (data) {
            var valid = (data.items || []).filter(function (i) {
                if (!i.valid) { window.SpeakUpDevice.remove(i.reference_no); }
                return i.valid;
            });
            if (!valid.length) { message('No anonymous submission saved on this device.'); return; }
            rows.innerHTML = valid.map(function (i) {
                return '<tr>'
                    + '<td class="text-nowrap">' + esc(i.reference_no) + (i.has_unread_reply ? ' <span class="badge badge-danger bg-danger">New reply</span>' : '') + '</td>'
                    + '<td>' + esc(i.category) + '</td>'
                    + '<td>' + esc(i.subject.length > 50 ? i.subject.slice(0, 50) + '…' : i.subject) + '</td>'
                    + '<td><span class="badge badge-' + (badge[i.status] || 'secondary') + ' bg-' + (badge[i.status] || 'secondary') + '">' + esc(i.status_label) + '</span></td>'
                    + '<td class="text-nowrap">' + esc(i.date) + '</td>'
                    + '<td class="text-nowrap"><form method="POST" action="' + esc(checkUrl) + '" class="d-inline">'
                    + '<input type="hidden" name="_token" value="' + esc(csrf) + '">'
                    + '<input type="hidden" name="reference_no" value="' + esc(i.reference_no) + '">'
                    + '<input type="hidden" name="follow_up_key" value="' + esc(keys[i.reference_no]) + '">'
                    + '<button type="submit" class="btn btn-sm btn-outline-primary">View</button></form> '
                    + '<button type="button" class="btn btn-sm btn-link text-muted su-forget" data-ref="' + esc(i.reference_no) + '">Remove</button></td>'
                    + '</tr>';
            }).join('');
        }).catch(function () { message('Could not load right now. Please refresh the page.'); });

        rows.addEventListener('click', function (e) {
            var btn = e.target.closest('.su-forget');
            if (!btn || !confirm('Remove this case from this device? You will need the follow-up key to check it again.')) return;
            window.SpeakUpDevice.remove(btn.getAttribute('data-ref'));
            btn.closest('tr').remove();
            if (!rows.children.length) { message('No anonymous submission saved on this device.'); }
        });
    })();
</script>
@endsection
