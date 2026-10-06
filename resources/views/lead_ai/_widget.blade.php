{{-- Loads window.LeadAiPanel once per page (Admin / Sales Manager / Operation Manager only).
     LeadAiPanel.mount(element, 'sales'|'operation', leadId) renders the AI review card into element;
     the card stays hidden when the user may not see that lead. --}}
@php
    $aiWidgetPrefix = \App\Support\LeadAi\LeadAiAccess::routePrefix();
    $aiWidgetTypes = \App\Support\LeadAi\LeadAiAccess::types();
@endphp
@if ($aiWidgetPrefix && $aiWidgetTypes)
<style>
    .lead-ai-card .ai-chip{display:inline-block;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600;margin-right:6px}
    .lead-ai-card .ai-sec{margin-top:14px}
    .lead-ai-card .ai-sec h6{font-weight:700;font-size:14px;margin-bottom:6px;color:#4b2bbf}
    .lead-ai-card .ai-sec ul{padding-left:18px;margin-bottom:0}
    .lead-ai-card .ai-sec li{margin-bottom:3px}
    .lead-ai-card .ai-box{background:#f7f5ff;border:1px solid #e3dcff;border-radius:8px;padding:10px 12px;white-space:pre-line}
    .lead-ai-card .ai-score{font-size:28px;font-weight:700;line-height:1}
    .lead-ai-card .lead-ai-body{max-height:460px;overflow-y:auto}
    .lead-ai-card .lead-ai-head{background:linear-gradient(90deg,#4b2bbf,#7b4fe0);color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:10px 16px}
</style>
<script>
window.LeadAiPanel = window.LeadAiPanel || (function () {
    var types = @json($aiWidgetTypes);
    var canReanalyze = @json(\App\Support\LeadAi\LeadAiAccess::canReanalyze());
    var urls = {
        status: @json(route($aiWidgetPrefix . '.ai_insights.status', [$aiWidgetTypes[0], 0])),
        analyze: @json(route($aiWidgetPrefix . '.ai_insights.analyze', [$aiWidgetTypes[0], 0]))
    };
    function url(kind, type, id) {
        return urls[kind].replace(/\/(sales|operation)\/0\/(status|analyze)$/, '/' + type + '/' + id + '/$2');
    }
    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    }

    function mount(el, type, id) {
        if (!el) return;
        el.innerHTML = '';
        el.style.display = 'none';
        clearTimeout(el._leadAiTimer);
        id = parseInt(id, 10);
        if (!id || types.indexOf(type) === -1) return;

        var token = (el._leadAiToken || 0) + 1;
        el._leadAiToken = token;
        el.innerHTML =
            '<div class="card mb-3 lead-ai-card">' +
              '<div class="lead-ai-head"><strong><i class="fas fa-robot"></i> AI Lead Review</strong>' +
                '<span style="display:flex;align-items:center;gap:8px;"><small style="opacity:.85;">Only managers &amp; Admin can see this</small>' +
                (canReanalyze ? '<button type="button" class="btn btn-sm btn-light lead-ai-run"><i class="fas fa-magic"></i> <span>Analyze with AI</span></button>' : '') + '</span>' +
              '</div>' +
              '<div class="card-body lead-ai-body"><div class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading AI review…</div></div>' +
            '</div>';
        var body = el.querySelector('.lead-ai-body');
        var btn = el.querySelector('.lead-ai-run');
        var hasResult = false;

        function setRunning(running) {
            if (!btn) return;
            btn.disabled = running;
            btn.querySelector('span').textContent = running ? 'Analyzing…' : (hasResult ? 'Re-analyze' : 'Analyze with AI');
        }

        function load() {
            fetch(url('status', type, id), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) {
                    if (!r.ok) throw r.status;
                    return r.json();
                })
                .then(function (d) {
                    if (el._leadAiToken !== token) return;
                    el.style.display = '';
                    if (body._leadAiHtml !== d.html) {
                        body.innerHTML = d.html;
                        body._leadAiHtml = d.html;
                    }
                    hasResult = !!body.querySelector('[data-ai-has-result]');
                    setRunning(d.running);
                    clearTimeout(el._leadAiTimer);
                    el._leadAiTimer = setTimeout(function next() {
                        if (el._leadAiToken !== token) return;
                        if (document.hidden) { el._leadAiTimer = setTimeout(next, 30000); return; }
                        load();
                    }, d.running ? 5000 : 30000);
                })
                .catch(function (status) {
                    if (el._leadAiToken !== token) return;
                    if (status === 403 || status === 404) { el.innerHTML = ''; el.style.display = 'none'; return; }
                    el.style.display = '';
                    body.innerHTML = '<div class="text-danger">Could not load the AI review.</div>';
                });
        }

        if (btn) btn.addEventListener('click', function () {
            setRunning(true);
            fetch(url('analyze', type, id), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                credentials: 'same-origin'
            }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
              .then(function (res) {
                  if (!res.ok) { alert(res.d.message || 'Could not start AI analysis.'); setRunning(false); return; }
                  setTimeout(load, 1500);
              })
              .catch(function () { setRunning(false); });
        });

        load();
    }

    function unmount(el) {
        if (!el) return;
        el._leadAiToken = (el._leadAiToken || 0) + 1;
        clearTimeout(el._leadAiTimer);
        el.innerHTML = '';
        el.style.display = 'none';
    }

    return { mount: mount, unmount: unmount, types: types };
})();
</script>
@endif
