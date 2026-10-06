{{--
    Location picker for the staff user form.
    Selected ids are posted as ONE comma-separated field (location_id_csv) because posting thousands of
    location_id[] values exceeds PHP max_input_vars and silently drops the fields after it.
    "all" means every location (expanded on the server).
--}}
@php
    $pickerOld = old('location_id_csv');
    if ($pickerOld !== null) {
        $pickerSelected = array_values(array_filter(explode(',', (string) $pickerOld), 'strlen'));
    } else {
        $pickerSelected = array_map('strval', array_filter((array) old('location_id', isset($user) ? ($user->location_id ?? []) : []), 'strlen'));
    }
    $pickerAll = in_array('all', $pickerSelected, true)
        || ($locations->count() > 0 && count(array_unique($pickerSelected)) >= $locations->count());
    $pickerData = $locations->map(fn ($l) => ['id' => (string) $l->id, 'name' => $l->name, 'state' => $l->state ?? ''])->values();
@endphp

<style>
    .ulp-box { border: 1px solid #ced4da; border-radius: 0.375rem; padding: 8px 10px; background: #fff; min-height: 38px; }
    .ulp-box.is-invalid { border-color: #dc3545; }
    .ulp-list { list-style: none; margin: 0 0 6px; padding: 0; }
    .ulp-list li { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px dashed #eee; }
    .ulp-list li:last-child { border-bottom: 0; }
    .ulp-state { color: #9ca3af; font-size: 0.85em; margin-left: 6px; }
    .ulp-remove { border: 0; background: none; color: #dc3545; font-size: 1.1rem; line-height: 1; cursor: pointer; }
    .ulp-summary { font-weight: 600; color: #374151; margin-bottom: 6px; }
    .ulp-empty { color: #9ca3af; margin-bottom: 6px; }
    .ulp-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 2000; display: none; align-items: center; justify-content: center; }
    .ulp-overlay.open { display: flex; }
    .ulp-modal { background: #fff; border-radius: 12px; width: 640px; max-width: 94vw; max-height: 86vh; display: flex; flex-direction: column; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    .ulp-modal-header, .ulp-modal-footer { padding: 12px 16px; display: flex; align-items: center; gap: 8px; }
    .ulp-modal-header { border-bottom: 1px solid #eee; justify-content: space-between; }
    .ulp-modal-footer { border-top: 1px solid #eee; justify-content: space-between; }
    .ulp-tools { padding: 10px 16px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; border-bottom: 1px solid #f3f3f3; }
    .ulp-tools input[type="search"] { flex: 1 1 220px; }
    .ulp-items { overflow-y: auto; padding: 4px 16px; flex: 1 1 auto; }
    .ulp-item { display: flex; align-items: center; gap: 8px; padding: 5px 0; border-bottom: 1px solid #f5f5f5; cursor: pointer; margin: 0; }
    .ulp-item input { margin: 0; }
    .ulp-close { border: 0; background: none; font-size: 1.5rem; line-height: 1; cursor: pointer; color: #666; }
</style>

<div class="ulp-box @error('location_id') is-invalid @enderror" id="ulpBox">
    <div id="ulpSelected"></div>
    <button type="button" class="btn btn-sm btn-outline-primary" id="ulpOpen">Locations chunein</button>
</div>
<input type="hidden" name="location_id_csv" id="ulpInput" value="">
@error('location_id')
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror

<div class="ulp-overlay" id="ulpOverlay" role="dialog" aria-modal="true">
    <div class="ulp-modal">
        <div class="ulp-modal-header">
            <strong>Locations</strong>
            <button type="button" class="ulp-close" id="ulpClose" aria-label="Close">&times;</button>
        </div>
        <div class="ulp-tools">
            <input type="search" class="form-control form-control-sm" id="ulpSearch" placeholder="City ya state search karein">
            <button type="button" class="btn btn-sm btn-outline-success" id="ulpSelectAll">Select All</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="ulpClearAll">Clear All</button>
            <label class="mb-0 small"><input type="checkbox" id="ulpOnlySelected"> Sirf selected dikhayein</label>
        </div>
        <div class="ulp-items" id="ulpItems"></div>
        <div class="ulp-modal-footer">
            <span class="small text-muted" id="ulpCount"></span>
            <div>
                <button type="button" class="btn btn-sm btn-secondary" id="ulpCancel">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="ulpApply">Done</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const LOCATIONS = @json($pickerData);
    const INLINE_LIMIT = 5;
    const byId = new Map(LOCATIONS.map(l => [l.id, l]));
    let selected = new Set(@json($pickerAll) ? LOCATIONS.map(l => l.id) : @json(array_values($pickerSelected)).filter(id => byId.has(id)));
    let draft = new Set();

    const el = id => document.getElementById(id);
    const esc = s => String(s || '').replace(/[&<>"]/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]));
    const isAll = set => LOCATIONS.length > 0 && set.size === LOCATIONS.length;

    function renderSummary() {
        const box = el('ulpSelected');
        el('ulpInput').value = isAll(selected) ? 'all' : Array.from(selected).join(',');

        if (selected.size === 0) {
            box.innerHTML = '<div class="ulp-empty">Koi location select nahi hai</div>';
            el('ulpOpen').textContent = 'Locations chunein';
            return;
        }
        if (selected.size <= INLINE_LIMIT && !isAll(selected)) {
            box.innerHTML = '<ul class="ulp-list">' + Array.from(selected).map(id => {
                const l = byId.get(id);
                return '<li><span>' + esc(l.name) + (l.state ? '<span class="ulp-state">' + esc(l.state) + '</span>' : '') + '</span>' +
                    '<button type="button" class="ulp-remove" data-id="' + esc(id) + '" title="Hatayein">&times;</button></li>';
            }).join('') + '</ul>';
            el('ulpOpen').textContent = 'Location jodein / badlein';
            return;
        }
        box.innerHTML = '<div class="ulp-summary">' +
            (isAll(selected) ? 'Saari locations selected (' + LOCATIONS.length + ')' : selected.size + ' locations selected') + '</div>';
        el('ulpOpen').textContent = 'View All / Edit';
    }

    function renderItems() {
        const q = el('ulpSearch').value.trim().toLowerCase();
        const onlySelected = el('ulpOnlySelected').checked;
        const rows = [];
        for (const l of LOCATIONS) {
            if (onlySelected && !draft.has(l.id)) continue;
            if (q && !(l.name.toLowerCase().includes(q) || l.state.toLowerCase().includes(q))) continue;
            rows.push('<label class="ulp-item"><input type="checkbox" value="' + esc(l.id) + '"' + (draft.has(l.id) ? ' checked' : '') + '>' +
                '<span>' + esc(l.name) + (l.state ? '<span class="ulp-state">' + esc(l.state) + '</span>' : '') + '</span></label>');
        }
        el('ulpItems').innerHTML = rows.length ? rows.join('') : '<div class="text-muted py-3 text-center">Koi location nahi mili</div>';
        renderCount();
    }

    function renderCount() {
        el('ulpCount').textContent = isAll(draft) ? 'Saari locations selected (' + LOCATIONS.length + ')' : draft.size + ' selected';
    }

    function visibleIds() {
        return Array.from(el('ulpItems').querySelectorAll('input[type="checkbox"]')).map(i => i.value);
    }

    function open() {
        draft = new Set(selected);
        el('ulpSearch').value = '';
        el('ulpOnlySelected').checked = false;
        renderItems();
        el('ulpOverlay').classList.add('open');
        el('ulpSearch').focus();
    }

    function close() {
        el('ulpOverlay').classList.remove('open');
    }

    el('ulpOpen').addEventListener('click', open);
    el('ulpClose').addEventListener('click', close);
    el('ulpCancel').addEventListener('click', close);
    el('ulpOverlay').addEventListener('click', e => { if (e.target === el('ulpOverlay')) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && el('ulpOverlay').classList.contains('open')) close(); });

    let searchTimer;
    el('ulpSearch').addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(renderItems, 150); });
    el('ulpOnlySelected').addEventListener('change', renderItems);

    el('ulpItems').addEventListener('change', e => {
        if (e.target.type !== 'checkbox') return;
        e.target.checked ? draft.add(e.target.value) : draft.delete(e.target.value);
        renderCount();
    });

    // With a search active these act on the filtered rows only.
    el('ulpSelectAll').addEventListener('click', () => {
        (el('ulpSearch').value.trim() ? visibleIds() : LOCATIONS.map(l => l.id)).forEach(id => draft.add(id));
        renderItems();
    });
    el('ulpClearAll').addEventListener('click', () => {
        el('ulpSearch').value.trim() ? visibleIds().forEach(id => draft.delete(id)) : draft.clear();
        renderItems();
    });

    el('ulpApply').addEventListener('click', () => {
        selected = new Set(LOCATIONS.map(l => l.id).filter(id => draft.has(id)));
        renderSummary();
        close();
    });

    el('ulpSelected').addEventListener('click', e => {
        const btn = e.target.closest('.ulp-remove');
        if (!btn) return;
        selected.delete(btn.dataset.id);
        renderSummary();
    });

    const form = el('ulpInput').form;
    if (form) {
        form.addEventListener('submit', e => {
            if (selected.size === 0) {
                e.preventDefault();
                el('ulpBox').classList.add('is-invalid');
                alert('Kam se kam ek location select karein.');
            }
        });
    }

    renderSummary();
})();
</script>
