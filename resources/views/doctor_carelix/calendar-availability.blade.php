@extends('doctor_carelix.layouts.app')
@section('title', 'Calendar availability')

@section('main')
<div class="content-wrapper dr-cal-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="card dr-cal-page-card">
                <div class="card-header dr-cal-header">
                    <h3 class="card-title mb-1">Calendar availability</h3>
                    <p class="mb-0">Pick dates, add time slots, and optionally add a break. Use <strong>whole month</strong> or <strong>whole year</strong> to apply same slots in bulk.</p>
                </div>
                <div class="card-body dr-cal-body">
                    <div class="row">
                        <div class="col-lg-5 mb-3 mb-lg-0">
                            <div class="dr-panel">
                                <div class="dr-panel-header">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Calendar navigation">
                                            <button type="button" class="btn btn-outline-secondary" id="calPrev">&larr; Prev</button>
                                            <button type="button" class="btn btn-outline-secondary" id="calNext">Next &rarr;</button>
                                        </div>
                                        <div class="dr-cal-title" id="calTitle"></div>
                                    </div>
                                </div>
                                <div class="dr-panel-body">
                                    <div class="table-responsive dr-cal-table-wrap">
                                        <table class="table table-bordered text-center mb-0 dr-cal-table">
                                            <thead>
                                                <tr><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th></tr>
                                            </thead>
                                            <tbody id="calBody"></tbody>
                                        </table>
                                    </div>
                                    <p class="small text-muted mb-0 mt-2" id="calStatus"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="dr-panel">
                                <div class="dr-panel-header">
                                    <h6 class="font-weight-bold mb-0">Selected dates</h6>
                                </div>
                                <div class="dr-panel-body">
                                    <p class="small text-muted mb-2" id="selDateLabel">Click dates to select. Click again to deselect.</p>
                                    <p class="small text-muted mb-3" id="selDateSlots"></p>
                                    <div class="form-row">
                                        <div class="form-group col-md-3 mb-2">
                                            <label class="small font-weight-bold">From</label>
                                            <input type="time" class="form-control form-control-sm" id="slotStart" value="09:00" step="300">
                                        </div>
                                        <div class="form-group col-md-3 mb-2">
                                            <label class="small font-weight-bold">To</label>
                                            <input type="time" class="form-control form-control-sm" id="slotEnd" value="18:00" step="300">
                                        </div>
                                        <div class="form-group col-md-3 mb-2">
                                            <label class="small font-weight-bold">Break from (optional)</label>
                                            <input type="time" class="form-control form-control-sm" id="slotBreakStart" step="300">
                                        </div>
                                        <div class="form-group col-md-3 mb-2">
                                            <label class="small font-weight-bold">Break to (optional)</label>
                                            <input type="time" class="form-control form-control-sm" id="slotBreakEnd" step="300">
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-primary" id="btnSaveDay">Add slot on selected dates</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnRemoveDay" disabled>Remove selected dates slots</button>
                                    </div>
                                </div>
                            </div>

                            <div class="dr-panel mt-3">
                                <div class="dr-panel-header">
                                    <h6 class="font-weight-bold mb-0">Apply same hours in bulk</h6>
                                </div>
                                <div class="dr-panel-body">
                                    <div class="form-row align-items-end">
                                        <div class="form-group col-md-3 mb-2">
                                            <label class="small font-weight-bold d-block">Scope</label>
                                            <select class="form-control form-control-sm" id="bulkScope">
                                                <option value="month">Whole month</option>
                                                <option value="year">Whole year</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2 mb-2" id="bulkMonthWrap">
                                            <label class="small font-weight-bold d-block">Month</label>
                                            <select class="form-control form-control-sm" id="bulkMonth"></select>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small font-weight-bold d-block">Year</label>
                                            <input type="number" class="form-control form-control-sm" id="bulkYear" min="2020" max="2100">
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small font-weight-bold d-block">From</label>
                                            <input type="time" class="form-control form-control-sm" id="bulkStart" value="09:00" step="300">
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small font-weight-bold d-block">To</label>
                                            <input type="time" class="form-control form-control-sm" id="bulkEnd" value="18:00" step="300">
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small font-weight-bold d-block">Break from</label>
                                            <input type="time" class="form-control form-control-sm" id="bulkBreakStart" step="300">
                                        </div>
                                        <div class="form-group col-md-1 mb-2">
                                            <label class="small font-weight-bold d-block">Break to</label>
                                            <input type="time" class="form-control form-control-sm" id="bulkBreakEnd" step="300">
                                        </div>
                                    </div>
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" class="custom-control-input" id="bulkWeekdays">
                                        <label class="custom-control-label small" for="bulkWeekdays">Weekdays only (Mon–Fri)</label>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-warning" id="btnBulkApply">Apply to all days in scope</button>
                                    <span class="ml-2 small text-muted" id="bulkMsg"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
    .dr-cal-wrapper {
        overflow-y: auto;
        max-height: calc(100vh - 120px);
        background: #f5f7fb;
    }
    .dr-cal-page-card {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }
    .dr-cal-header {
        background: linear-gradient(90deg, #f7941d 0%, #ff7a18 100%);
        color: #fff;
        padding: 1rem 1.25rem;
        border: 0;
    }
    .dr-cal-header p {
        opacity: 0.95;
        font-size: 0.88rem;
    }
    .dr-cal-body {
        background: #f5f7fb;
        padding: 1.25rem;
    }
    .dr-panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
    }
    .dr-panel-header {
        padding: 0.85rem 1rem;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
    }
    .dr-panel-body {
        padding: 1rem;
    }
    .dr-cal-title {
        font-weight: 800;
        color: #334155;
        font-size: 0.98rem;
        text-align: right;
        min-width: 130px;
    }
    .dr-cal-table-wrap {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }
    .dr-cal-table {
        table-layout: fixed;
        margin-bottom: 0;
        background: #fff;
    }
    .dr-cal-table thead th {
        background: #f1f5f9;
        color: #475569;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.35px;
        font-size: 0.72rem;
        padding: 0.45rem 0.2rem;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }
    .dr-cal-table td {
        padding: 0;
        height: 38px;
        font-weight: 700;
        font-size: 0.86rem;
        color: #0f172a;
        vertical-align: middle;
    }
    .dr-cal-table td:not(:empty) {
        transition: background-color 0.15s ease, outline-color 0.15s ease;
    }
    .dr-cal-table td:not(:empty):hover {
        background: #fff7ed;
    }
    .dr-cal-table td {
        border-color: #edf2f7;
    }
    .gap-2 { gap: 0.5rem; }
    @media (max-width: 991px) {
        .dr-cal-body { padding: 0.95rem; }
        .dr-cal-title { min-width: 0; font-size: 0.95rem; }
        .dr-panel-body { padding: 0.9rem; }
    }
</style>
@endpush

<script>
(function() {
    var dataUrl = @json(route('doctor_portal.availability.calendar_slots'));
    var saveUrl = @json(route('doctor_portal.availability.calendar_save'));
    var delUrlTpl = @json(url('/doctor-portal/availability/calendar-delete/__ID__'));
    var delDateUrl = @json(route('doctor_portal.availability.calendar_delete_by_date'));
    var token = @json(csrf_token());

    var view = new Date();
    view.setDate(1);
    var slotsByDate = {};
    var selectedDates = [];

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function ymd(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }
    function parseYmd(s) {
        var p = s.split('-');
        return new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    }
    function monthTitle(d) {
        return d.toLocaleString(undefined, { month: 'long', year: 'numeric' });
    }

    function loadRange() {
        var from = new Date(view.getFullYear(), view.getMonth(), 1);
        var to = new Date(view.getFullYear(), view.getMonth() + 1, 0);
        document.getElementById('calStatus').textContent = 'Loading…';
        $.getJSON(dataUrl, { from: ymd(from), to: ymd(to) })
            .done(function(res) {
                slotsByDate = {};
                if (res.success && res.slots) {
                    res.slots.forEach(function(s) {
                        if (!slotsByDate[s.slot_date]) slotsByDate[s.slot_date] = [];
                        slotsByDate[s.slot_date].push(s);
                    });
                }
                document.getElementById('calStatus').textContent = '';
                render();
            })
            .fail(function() {
                document.getElementById('calStatus').textContent = 'Could not load calendar.';
            });
    }

    function render() {
        document.getElementById('calTitle').textContent = monthTitle(view);
        var tbody = document.getElementById('calBody');
        tbody.innerHTML = '';
        var first = new Date(view.getFullYear(), view.getMonth(), 1);
        var startPad = (first.getDay() + 6) % 7;
        var daysInMonth = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
        var today = new Date();
        today.setHours(0,0,0,0);
        var row = document.createElement('tr');
        var i;
        for (i = 0; i < startPad; i++) {
            row.appendChild(document.createElement('td'));
        }
        for (var d = 1; d <= daysInMonth; d++) {
            var cellDate = new Date(view.getFullYear(), view.getMonth(), d);
            var cellYmd = ymd(cellDate);
            var td = document.createElement('td');
            td.style.cursor = 'pointer';
            td.textContent = d;
            var slotList = slotsByDate[cellYmd] || [];
            if (cellDate < today) {
                td.style.background = '#f1f3f5';
                td.style.color = '#adb5bd';
                td.style.cursor = 'not-allowed';
            } else if (slotList.length > 0) {
                td.style.background = '#e7f5ff';
                td.title = slotList.map(function(s){ return s.label || ((s.time_start || '') + ' – ' + (s.time_end || '')); }).join(' | ');
            }
            if (selectedDates.indexOf(cellYmd) !== -1) {
                td.style.outline = '2px solid #F07F28';
            }
            (function(ymd, c) {
                td.addEventListener('click', function() {
                    if (c < today) return;
                    var idx = selectedDates.indexOf(ymd);
                    if (idx === -1) selectedDates.push(ymd);
                    else selectedDates.splice(idx, 1);
                    selectedDates.sort();
                    document.getElementById('selDateLabel').textContent = selectedDates.length
                        ? ('Selected (' + selectedDates.length + '): ' + selectedDates.join(', '))
                        : 'Click dates to select. Click again to deselect.';
                    var last = selectedDates.length ? selectedDates[selectedDates.length - 1] : null;
                    var rows = last ? (slotsByDate[last] || []) : [];
                    var first = rows.length ? rows[0] : null;
                    document.getElementById('slotStart').value = first ? first.time_start : '09:00';
                    document.getElementById('slotEnd').value = first ? first.time_end : '18:00';
                    document.getElementById('slotBreakStart').value = first && first.break_start ? first.break_start : '';
                    document.getElementById('slotBreakEnd').value = first && first.break_end ? first.break_end : '';
                    document.getElementById('selDateSlots').textContent = rows.length
                        ? ('Existing slots on last selected date: ' + rows.map(function(s){ return s.label || (s.time_start + ' - ' + s.time_end); }).join(', '))
                        : (selectedDates.length ? 'No slots on last selected date.' : '');
                    var hasSlots = selectedDates.some(function(dt){ return (slotsByDate[dt] || []).length > 0; });
                    document.getElementById('btnRemoveDay').disabled = selectedDates.length === 0 || !hasSlots;
                    render();
                });
            })(cellYmd, cellDate);
            row.appendChild(td);
            if ((startPad + d) % 7 === 0) {
                tbody.appendChild(row);
                row = document.createElement('tr');
            }
        }
        if (row.children.length > 0) {
            while (row.children.length < 7) {
                row.appendChild(document.createElement('td'));
            }
            tbody.appendChild(row);
        }
    }

    document.getElementById('calPrev').addEventListener('click', function() {
        view.setMonth(view.getMonth() - 1);
        selectedDates = [];
        loadRange();
    });
    document.getElementById('calNext').addEventListener('click', function() {
        view.setMonth(view.getMonth() + 1);
        selectedDates = [];
        loadRange();
    });

    document.getElementById('btnSaveDay').addEventListener('click', function() {
        if (selectedDates.length === 0) {
            if (typeof toastr !== 'undefined') toastr.warning('Select one or more future dates on the calendar.');
            else alert('Select one or more future dates on the calendar.');
            return;
        }
        var payload = {
            dates: selectedDates.slice(),
            time_start: document.getElementById('slotStart').value,
            time_end: document.getElementById('slotEnd').value,
            break_start: document.getElementById('slotBreakStart').value || null,
            break_end: document.getElementById('slotBreakEnd').value || null
        };
        $.ajax({
            url: saveUrl,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            headers: { 'X-CSRF-TOKEN': token }
        }).done(function(res) {
            if (res.success) {
                if (typeof toastr !== 'undefined') toastr.success(res.message || 'Saved');
                loadRange();
            } else {
                if (typeof toastr !== 'undefined') toastr.error(res.message || 'Failed');
            }
        }).fail(function(xhr) {
            var m = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed';
            if (typeof toastr !== 'undefined') toastr.error(m);
            else alert(m);
        });
    });

    document.getElementById('btnRemoveDay').addEventListener('click', function() {
        if (selectedDates.length === 0) return;
        var targets = selectedDates.filter(function(dt){ return (slotsByDate[dt] || []).length > 0; });
        if (targets.length === 0) return;
        var reqs = targets.map(function(dt) {
            return $.ajax({
                url: delDateUrl,
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ slot_date: dt }),
                headers: { 'X-CSRF-TOKEN': token }
            });
        });
        $.when.apply($, reqs).done(function() {
            if (typeof toastr !== 'undefined') toastr.success('Removed');
            loadRange();
        });
    });

    var bulkMonth = document.getElementById('bulkMonth');
    for (var m = 1; m <= 12; m++) {
        var o = document.createElement('option');
        o.value = m;
        o.textContent = new Date(2000, m - 1, 1).toLocaleString(undefined, { month: 'long' });
        bulkMonth.appendChild(o);
    }
    document.getElementById('bulkYear').value = view.getFullYear();
    bulkMonth.value = view.getMonth() + 1;

    document.getElementById('bulkScope').addEventListener('change', function() {
        document.getElementById('bulkMonthWrap').style.display = this.value === 'month' ? '' : 'none';
    });

    document.getElementById('btnBulkApply').addEventListener('click', function() {
        var scope = document.getElementById('bulkScope').value;
        var payload = {
            bulk: scope,
            year: parseInt(document.getElementById('bulkYear').value, 10),
            time_start: document.getElementById('bulkStart').value,
            time_end: document.getElementById('bulkEnd').value,
            break_start: document.getElementById('bulkBreakStart').value || null,
            break_end: document.getElementById('bulkBreakEnd').value || null,
            weekdays_only: document.getElementById('bulkWeekdays').checked
        };
        if (scope === 'month') {
            payload.month = parseInt(document.getElementById('bulkMonth').value, 10);
        }
        document.getElementById('bulkMsg').textContent = 'Saving…';
        $.ajax({
            url: saveUrl,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            headers: { 'X-CSRF-TOKEN': token }
        }).done(function(res) {
            document.getElementById('bulkMsg').textContent = '';
            if (res.success) {
                if (typeof toastr !== 'undefined') toastr.success(res.message || 'Saved');
                loadRange();
            } else {
                if (typeof toastr !== 'undefined') toastr.error(res.message || 'Failed');
            }
        }).fail(function(xhr) {
            document.getElementById('bulkMsg').textContent = '';
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed';
            if (typeof toastr !== 'undefined') toastr.error(msg);
        });
    });

    loadRange();
})();
</script>
@endsection
