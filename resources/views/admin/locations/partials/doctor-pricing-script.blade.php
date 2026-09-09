<script>
document.addEventListener('DOMContentLoaded', function () {
    var servicesData = [];
    try {
        var jsonEl = document.getElementById('loc_doctor_services_json');
        if (jsonEl) servicesData = JSON.parse(jsonEl.textContent || '[]');
    } catch (e) { servicesData = []; }

    var blocksEl = document.getElementById('loc_doctor_pricing_blocks');
    var tplEl = document.getElementById('loc_doctor_pricing_block_tpl');
    var addBlockBtn = document.getElementById('loc_add_doctor_pricing_block');
    var nextBlockIndex = 0;

    document.querySelectorAll('.loc-pricing-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            var tab = pill.getAttribute('data-pricing-tab');
            document.querySelectorAll('.loc-pricing-pill').forEach(function (p) { p.classList.remove('active'); });
            document.querySelectorAll('.loc-pricing-panel').forEach(function (p) { p.classList.remove('active'); });
            pill.classList.add('active');
            var panel = document.querySelector('[data-pricing-panel="' + tab + '"]');
            if (panel) panel.classList.add('active');
        });
    });

    function serviceById(id) {
        id = String(id);
        for (var i = 0; i < servicesData.length; i++) {
            if (String(servicesData[i].id) === id) return servicesData[i];
        }
        return null;
    }

    function fillSubSelect(block, serviceId, selectedSubId) {
        var subSel = block.querySelector('.loc-doc-sub-select');
        var subWrap = subSel ? subSel.closest('.form-group') : null;
        var hint = block.querySelector('.loc-doc-sub-hint');
        if (!subSel) return;

        var svc = serviceById(serviceId);
        var subs = (svc && svc.sub_services) ? svc.sub_services : [];
        var hasSubs = subs.length > 0;

        subSel.innerHTML = '';
        if (hasSubs) {
            var ph = document.createElement('option');
            ph.value = '';
            ph.textContent = 'Select sub-service';
            subSel.appendChild(ph);
            subs.forEach(function (sub) {
                var opt = document.createElement('option');
                opt.value = String(sub.id);
                opt.textContent = sub.name;
                subSel.appendChild(opt);
            });
            if (selectedSubId) {
                subSel.value = String(selectedSubId);
            }
            subSel.required = true;
            if (hint) {
                hint.textContent = 'Is service ke andar sub-services hain — sub-service select karke uski Online / Home / Clinic price set karein (service-level price use nahi hogi).';
            }
        } else {
            var none = document.createElement('option');
            none.value = '';
            none.textContent = '— Service-level price (no sub-service) —';
            none.selected = true;
            subSel.appendChild(none);
            subSel.required = false;
            if (hint) {
                hint.textContent = 'Is service ke andar sub-service nahi hai — yahan set ki gayi price service-level par save hogi.';
            }
        }

        if (subWrap) subWrap.style.display = '';
    }

    function syncModeRow(block, modeKey, enabled) {
        var chip = block.querySelector('.loc-mode-chip[data-mode="' + modeKey + '"]');
        var row = block.querySelector('.loc-mode-row[data-mode="' + modeKey + '"]');
        var cb = chip ? chip.querySelector('.loc-mode-chip-cb') : null;
        if (chip) chip.classList.toggle('active', !!enabled);
        if (cb) cb.checked = !!enabled;
        if (row) row.classList.toggle('d-none', !enabled);
    }

    function updateFinalAllowed(row) {
        var inp = row.querySelector('.loc-doctor-max-price');
        var span = row.querySelector('.loc-final-val');
        if (!inp || !span) return;
        var v = parseFloat(inp.value);
        span.textContent = isNaN(v) ? '0' : String(Math.round(v));
    }

    function wireBlock(block) {
        var idxAttr = block.getAttribute('data-block-index');
        var n = parseInt(idxAttr, 10);
        if (!isNaN(n) && n >= nextBlockIndex) nextBlockIndex = n + 1;

        var svcSel = block.querySelector('.loc-doc-service-select');
        var subSel = block.querySelector('.loc-doc-sub-select');
        if (svcSel && !svcSel.getAttribute('data-wired')) {
            svcSel.setAttribute('data-wired', '1');
            var savedSubId = block.getAttribute('data-saved-sub-id') || '';
            if (!savedSubId && subSel) {
                savedSubId = subSel.value || '';
            }
            fillSubSelect(block, svcSel.value, savedSubId);
            svcSel.addEventListener('change', function () {
                block.setAttribute('data-saved-sub-id', '');
                fillSubSelect(block, svcSel.value, '');
            });
            if (subSel && !subSel.getAttribute('data-wired')) {
                subSel.setAttribute('data-wired', '1');
                subSel.addEventListener('change', function () {
                    block.setAttribute('data-saved-sub-id', subSel.value || '');
                });
            }
        }

        block.querySelectorAll('.loc-mode-chip').forEach(function (chip) {
            if (chip.getAttribute('data-wired')) return;
            chip.setAttribute('data-wired', '1');
            chip.addEventListener('click', function (e) {
                if (e.target.closest('.loc-mode-row-remove')) return;
                var mode = chip.getAttribute('data-mode');
                var cb = chip.querySelector('.loc-mode-chip-cb');
                var next = cb ? !cb.checked : true;
                syncModeRow(block, mode, next);
            });
        });

        block.querySelectorAll('.loc-mode-row-remove').forEach(function (btn) {
            if (btn.getAttribute('data-wired')) return;
            btn.setAttribute('data-wired', '1');
            btn.addEventListener('click', function () {
                var row = btn.closest('.loc-mode-row');
                if (!row) return;
                var mode = row.getAttribute('data-mode');
                syncModeRow(block, mode, false);
            });
        });

        block.querySelectorAll('.loc-doctor-max-price').forEach(function (inp) {
            if (inp.getAttribute('data-wired')) return;
            inp.setAttribute('data-wired', '1');
            inp.addEventListener('input', function () {
                updateFinalAllowed(inp.closest('.loc-mode-row'));
            });
        });

        block.querySelectorAll('.loc-mode-row').forEach(function (row) {
            updateFinalAllowed(row);
        });

        var removeBlock = block.querySelector('.loc-remove-doc-block');
        if (removeBlock && !removeBlock.getAttribute('data-wired')) {
            removeBlock.setAttribute('data-wired', '1');
            removeBlock.addEventListener('click', function () {
                var all = blocksEl.querySelectorAll('.loc-doc-block');
                if (all.length <= 1) {
                    svcSel.value = '';
                    fillSubSelect(block, '', '');
                    block.querySelectorAll('.loc-mode-row').forEach(function (row) {
                        var mode = row.getAttribute('data-mode');
                        syncModeRow(block, mode, false);
                        row.querySelectorAll('input[type="number"]').forEach(function (i) { i.value = ''; });
                    });
                    return;
                }
                block.remove();
                renumberBlocks();
            });
        }
    }

    function renumberBlocks() {
        blocksEl.querySelectorAll('.loc-doc-block').forEach(function (b, i) {
            var num = b.querySelector('.loc-block-num');
            if (num) num.textContent = String(i + 1);
        });
    }

    function addBlock() {
        if (!tplEl || !blocksEl) return;
        var html = tplEl.innerHTML.replace(/__BLOCK_INDEX__/g, String(nextBlockIndex));
        nextBlockIndex++;
        var wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        var block = wrap.firstElementChild;
        if (!block) return;
        blocksEl.appendChild(block);
        wireBlock(block);
        renumberBlocks();
    }

    if (blocksEl) {
        blocksEl.querySelectorAll('.loc-doc-block').forEach(wireBlock);
        renumberBlocks();
    }

    if (addBlockBtn) {
        addBlockBtn.addEventListener('click', addBlock);
    }
});
</script>
