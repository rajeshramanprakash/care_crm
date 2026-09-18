<script>
document.addEventListener('DOMContentLoaded', function () {
    var servicesOptions = [];
    try {
        var j = document.getElementById('loc_vendor_services_json');
        if (j) servicesOptions = JSON.parse(j.textContent || '[]');
    } catch (e) {}

    var rowTpl = document.getElementById('loc_provider_service_row_tpl');
    var counters = {};

    function serviceById(id) {
        id = String(id);
        for (var i = 0; i < servicesOptions.length; i++) {
            if (String(servicesOptions[i].id) === id) return servicesOptions[i];
        }
        return null;
    }

    function escapeHtml(t) {
        var d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    function optionsHtml() {
        var h = '<option value="">Select service</option>';
        servicesOptions.forEach(function (s) {
            h += '<option value="' + s.id + '">' + escapeHtml(s.name) + '</option>';
        });
        return h;
    }

    function fillSubSelect(row, serviceId, selectedSubId) {
        var subSel = row.querySelector('.loc-provider-sub-select');
        var hint = row.querySelector('.loc-provider-sub-hint');
        if (!subSel) return;

        var svc = serviceById(serviceId);
        var subs = (svc && svc.sub_services) ? svc.sub_services : [];
        var hasSubs = subs.length > 0;

        subSel.innerHTML = '';
        if (hasSubs) {
            var ph = document.createElement('option');
            ph.value = '0';
            ph.textContent = 'Select sub-service';
            subSel.appendChild(ph);
            subs.forEach(function (sub) {
                var opt = document.createElement('option');
                opt.value = String(sub.id);
                opt.textContent = sub.name;
                subSel.appendChild(opt);
            });
            if (selectedSubId && String(selectedSubId) !== '0') {
                subSel.value = String(selectedSubId);
            }
            subSel.required = true;
            if (hint) {
                hint.textContent = '';
            }
        } else {
            var none = document.createElement('option');
            none.value = '0';
            none.textContent = '— Service-level (no sub-service) —';
            none.selected = true;
            subSel.appendChild(none);
            subSel.required = false;
            if (hint) {
                hint.textContent = '';
            }
        }
    }

    function wireProviderRow(row) {
        if (row.getAttribute('data-provider-wired') === '1') return;
        row.setAttribute('data-provider-wired', '1');

        var svcSel = row.querySelector('.loc-provider-service-select');
        var subSel = row.querySelector('.loc-provider-sub-select');
        if (!svcSel) return;

        var savedSubId = row.getAttribute('data-saved-sub-id') || '0';
        if (subSel && subSel.value && subSel.value !== '0') {
            savedSubId = subSel.value;
        }
        fillSubSelect(row, svcSel.value, savedSubId);

        svcSel.addEventListener('change', function () {
            row.setAttribute('data-saved-sub-id', '0');
            fillSubSelect(row, svcSel.value, '0');
        });

        if (subSel) {
            subSel.addEventListener('change', function () {
                row.setAttribute('data-saved-sub-id', subSel.value || '0');
            });
        }
    }

    function updateRemoveButtons(container) {
        var rows = container.querySelectorAll('.service-row');
        rows.forEach(function (row) {
            var btn = row.querySelector('.remove-service');
            if (btn) btn.style.display = rows.length > 1 ? '' : 'none';
        });
    }

    function nextIndex(prefix) {
        if (!counters[prefix]) {
            counters[prefix] = 0;
            document.querySelectorAll('[name^="' + prefix + '["]').forEach(function (el) {
                var m = el.name.match(new RegExp('^' + prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\[(\\d+)\\]'));
                if (m) {
                    var n = parseInt(m[1], 10);
                    if (!isNaN(n) && n >= counters[prefix]) counters[prefix] = n + 1;
                }
            });
        }
        var n = counters[prefix];
        counters[prefix]++;
        return n;
    }

    function initContainer(containerId, prefix) {
        var container = document.getElementById(containerId);
        if (!container) return;
        container.querySelectorAll('.service-row').forEach(wireProviderRow);
        updateRemoveButtons(container);
    }

    initContainer('vendor-services-container', 'vendor_services');
    initContainer('freelancer-services-container', 'freelancer_services');

    document.querySelectorAll('.loc-add-provider-service').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var containerId = btn.getAttribute('data-container');
            var prefix = btn.getAttribute('data-prefix');
            var container = document.getElementById(containerId);
            if (!container || !rowTpl) return;

            var idx = nextIndex(prefix);
            var html = rowTpl.innerHTML
                .replace(/__ROW_INDEX__/g, String(idx))
                .replace(/__INPUT_PREFIX__/g, prefix);

            var wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            var row = wrap.firstElementChild;
            if (!row) return;

            var sel = row.querySelector('.service-select');
            if (sel) sel.innerHTML = optionsHtml();

            container.appendChild(row);
            wireProviderRow(row);
            updateRemoveButtons(container);
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.remove-service')) return;
        var row = e.target.closest('.service-row');
        var container = row ? row.closest('.loc-services-scroll') : null;
        if (row) row.remove();
        if (container) updateRemoveButtons(container);
    });
});
</script>
