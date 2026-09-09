<style>
    .av-tags-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
    @media (max-width: 640px) { .av-tags-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .av-tag-pill { display: block; margin: 0; cursor: pointer; position: relative; }
    .av-tag-pill input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .av-tag-pill-inner {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        min-height: 36px; padding: 6px 10px; border-radius: 8px; border: 2px solid #ea8a2b;
        background: #fff; font-size: 0.8rem; font-weight: 600;
    }
    .av-tag-pill input:checked + .av-tag-pill-inner {
        background: linear-gradient(135deg, #fe992e 0%, #e8892a 100%); color: #fff; border-color: #e8892a;
    }
    .av-sub-block {
        border: 1px solid #e8e4df; border-radius: 10px; padding: 0.75rem; margin-bottom: 0.65rem; background: #faf9f7;
    }
    .av-sub-block-head { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem; }
    .av-sub-remove-btn {
        padding: 0.25rem 0.55rem; font-size: 0.75rem; border-radius: 5px;
        border: 1px solid #fecaca; background: #fff; color: #b91c1c;
    }
    .av-price-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
    .av-price-table th, .av-price-table td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: middle; }
    .av-price-table th { background: #f9fafb; font-weight: 700; }
    .av-loc-default { display: block; font-size: 0.72rem; color: #888; margin-top: 2px; }
    .av-price-input { max-width: 110px; }
</style>
<script>
window.AdminVendorServices = (function () {
    var servicesUrl = @json(route('register.location-provider-services'));
    var forms = {};

    function escHtml(t) {
        var d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    function buildTagPill(tag, inputClass, checked) {
        var $lbl = window.jQuery('<label class="av-tag-pill"/>');
        var $cb = window.jQuery('<input type="checkbox"/>').addClass(inputClass).val(tag).prop('checked', !!checked);
        var $inner = window.jQuery('<span class="av-tag-pill-inner"/>').text(tag);
        return $lbl.append($cb).append($inner);
    }

    function serviceById(formState, id) {
        id = String(id);
        for (var i = 0; i < formState.locationServices.length; i++) {
            if (String(formState.locationServices[i].id) === id) {
                return formState.locationServices[i];
            }
        }
        return null;
    }

    function defaultPrice(svc, subId) {
        if (!svc || !svc.prices) {
            return { price_12hr: null, price_24hr: null, price_onetime: null };
        }
        var key = String(subId || 0);
        return svc.prices[key] || svc.prices['0'] || { price_12hr: null, price_24hr: null, price_onetime: null };
    }

    function getBlockState(formState, blockIndex) {
        if (!formState.blocks[blockIndex]) {
            formState.blocks[blockIndex] = {
                service_id: null,
                selectedSubMap: {},
                selectedServiceTags: [],
                priceOverrides: {}
            };
        }
        return formState.blocks[blockIndex];
    }

    function renderServiceOptions($select, formState, selectedId) {
        $select.empty().append('<option value="">Select service</option>');
        formState.locationServices.forEach(function (svc) {
            var usedElsewhere = false;
            Object.keys(formState.blocks).forEach(function (bi) {
                var b = formState.blocks[bi];
                if (String(bi) !== String($select.closest('.av-service-block').attr('data-block-index')) &&
                    b && String(b.service_id) === String(svc.id)) {
                    usedElsewhere = true;
                }
            });
            if (usedElsewhere) return;
            var $opt = window.jQuery('<option/>').val(svc.id).text(svc.name);
            if (String(selectedId) === String(svc.id)) $opt.prop('selected', true);
            $select.append($opt);
        });
    }

    function renderSubServiceTagBlock(blockState, sub) {
        var sid = String(sub.id);
        var entry = blockState.selectedSubMap[sid] || { name: sub.name, tags: [] };
        blockState.selectedSubMap[sid] = entry;
        var $block = window.jQuery('<div class="av-sub-block"/>').attr('data-sub-id', sid);
        var $head = window.jQuery('<div class="av-sub-block-head"/>');
        $head.append(window.jQuery('<strong/>').text(sub.name));
        $head.append(window.jQuery('<button type="button" class="av-sub-remove-btn"/>').html('<i class="fas fa-times"></i> Remove'));
        $block.append($head);
        var tags = sub.tags || [];
        if (tags.length) {
            $block.append('<small class="text-muted d-block mb-2">Tags select karein:</small>');
            var $grid = window.jQuery('<div class="av-tags-grid"/>');
            tags.forEach(function (tag) {
                $grid.append(buildTagPill(tag, 'av-sub-tag-cb', entry.tags.indexOf(tag) !== -1));
            });
            $block.append($grid);
        }
        return $block;
    }

    function refreshSubAddSelect($blockEl, blockState, svc) {
        var $sel = $blockEl.find('.av-sub-add-select').empty().append('<option value="">Select sub-service to add</option>');
        if (!svc || !svc.sub_services) return;
        svc.sub_services.forEach(function (sub) {
            if (blockState.selectedSubMap[String(sub.id)]) return;
            $sel.append(window.jQuery('<option/>').val(sub.id).text(sub.name));
        });
    }

    function renderServiceTags($blockEl, blockState, svc) {
        var $wrap = $blockEl.find('.av-service-tags-wrap');
        var $grid = $blockEl.find('.av-service-tags-grid').empty();
        var tags = svc.tags || [];
        if (!tags.length) { $wrap.hide(); return; }
        $wrap.show();
        tags.forEach(function (tag) {
            $grid.append(buildTagPill(tag, 'av-service-tag-cb', blockState.selectedServiceTags.indexOf(tag) !== -1));
        });
    }

    function pricingItems(blockState, svc) {
        if (!svc) return [];
        var subs = svc.sub_services || [];
        if (subs.length) {
            return Object.keys(blockState.selectedSubMap).map(function (sid) {
                return { sub_service_id: parseInt(sid, 10), label: blockState.selectedSubMap[sid].name };
            });
        }
        return [{ sub_service_id: 0, label: svc.name }];
    }

    function getEffectivePrice(blockState, svc, subId) {
        var key = String(subId || 0);
        var def = defaultPrice(svc, subId);
        var ov = blockState.priceOverrides[key] || {};
        return {
            price_12hr: ov.price_12hr != null && ov.price_12hr !== '' ? ov.price_12hr : def.price_12hr,
            price_24hr: ov.price_24hr != null && ov.price_24hr !== '' ? ov.price_24hr : def.price_24hr,
            price_onetime: ov.price_onetime != null && ov.price_onetime !== '' ? ov.price_onetime : def.price_onetime,
            defaults: def
        };
    }

    function renderPricePanel($blockEl, blockState, svc) {
        var items = pricingItems(blockState, svc);
        var $wrap = $blockEl.find('.av-price-panel');
        var $tableWrap = $blockEl.find('.av-price-table-wrap').empty();
        if (!items.length || !svc) { $wrap.hide(); return; }
        $wrap.show();
        var $table = window.jQuery('<table class="av-price-table"/>');
        $table.append('<thead><tr><th>Service / Sub-service</th><th>12hr (₹)</th><th>24hr (₹)</th><th>One-time (₹)</th></tr></thead>');
        var $tbody = window.jQuery('<tbody/>');
        items.forEach(function (item) {
            var eff = getEffectivePrice(blockState, svc, item.sub_service_id);
            var $tr = window.jQuery('<tr/>');
            $tr.append(window.jQuery('<td/>').html('<strong>' + escHtml(item.label) + '</strong>'));
            ['price_12hr', 'price_24hr', 'price_onetime'].forEach(function (field) {
                var defVal = eff.defaults[field];
                var $td = window.jQuery('<td/>');
                if (defVal != null && defVal !== '') {
                    $td.append(window.jQuery('<span class="av-loc-default"/>').text('Loc: ₹' + defVal));
                }
                $td.append(
                    window.jQuery('<input type="number" min="0" step="0.01" class="form-control form-control-sm av-price-input"/>')
                        .attr('data-sub-id', item.sub_service_id)
                        .attr('data-field', field)
                        .val(eff[field] != null ? eff[field] : '')
                );
                $tr.append($td);
            });
            $tbody.append($tr);
        });
        $table.append($tbody);
        $tableWrap.append($table);
    }

    function refreshBlockUI(formState, blockIndex) {
        var $blockEl = formState.$container.find('.av-service-block[data-block-index="' + blockIndex + '"]');
        var blockState = getBlockState(formState, blockIndex);
        var svc = serviceById(formState, blockState.service_id);
        var $subPicker = $blockEl.find('.av-sub-picker-wrap');
        var $subStack = $blockEl.find('.av-sub-tags-stack').empty();

        if (!svc) {
            $subPicker.hide();
            $blockEl.find('.av-service-tags-wrap').hide();
            $blockEl.find('.av-price-panel').hide();
            return;
        }

        var subs = svc.sub_services || [];
        if (subs.length) {
            $subPicker.show();
            $blockEl.find('.av-service-tags-wrap').hide();
            refreshSubAddSelect($blockEl, blockState, svc);
            Object.keys(blockState.selectedSubMap).forEach(function (sid) {
                var sub = subs.find(function (s) { return String(s.id) === String(sid); });
                if (sub) $subStack.append(renderSubServiceTagBlock(blockState, sub));
            });
        } else {
            $subPicker.hide();
            renderServiceTags($blockEl, blockState, svc);
        }
        renderPricePanel($blockEl, blockState, svc);
    }

    function onServiceChange(formState, blockIndex, serviceId) {
        var blockState = getBlockState(formState, blockIndex);
        blockState.service_id = serviceId ? parseInt(serviceId, 10) : null;
        blockState.selectedSubMap = {};
        blockState.selectedServiceTags = [];
        blockState.priceOverrides = {};
        refreshBlockUI(formState, blockIndex);
    }

    function serializeForm(formPrefix) {
        var formState = forms[formPrefix];
        if (!formState) return [];
        var payload = [];
        formState.$container.find('.av-service-block').each(function () {
            var blockIndex = window.jQuery(this).attr('data-block-index');
            var blockState = getBlockState(formState, blockIndex);
            if (!blockState.service_id) return;
            var svc = serviceById(formState, blockState.service_id);
            var subPayload = [];
            if (svc && (svc.sub_services || []).length) {
                Object.keys(blockState.selectedSubMap).forEach(function (sid) {
                    var entry = blockState.selectedSubMap[sid];
                    if (!entry) return;
                    subPayload.push({
                        sub_service_id: parseInt(sid, 10),
                        sub_service_name: entry.name,
                        tags: entry.tags.slice()
                    });
                });
            } else if (svc && blockState.selectedServiceTags.length) {
                subPayload.push({
                    sub_service_id: 0,
                    sub_service_name: null,
                    tags: blockState.selectedServiceTags.slice()
                });
            }
            var pricePayload = [];
            Object.keys(blockState.priceOverrides).forEach(function (subKey) {
                var ov = blockState.priceOverrides[subKey];
                pricePayload.push({
                    service_sub_service_id: parseInt(subKey, 10),
                    price_12hr: ov.price_12hr != null && ov.price_12hr !== '' ? ov.price_12hr : null,
                    price_24hr: ov.price_24hr != null && ov.price_24hr !== '' ? ov.price_24hr : null,
                    price_onetime: ov.price_onetime != null && ov.price_onetime !== '' ? ov.price_onetime : null
                });
            });
            payload.push({
                service_id: blockState.service_id,
                service_name: svc ? svc.name : '',
                sub_services: subPayload,
                price_overrides: pricePayload
            });
        });
        window.jQuery('#' + formPrefix + '_vendor_services_json').val(JSON.stringify(payload));
        return payload;
    }

    function addBlock(formPrefix, restoreData) {
        var formState = forms[formPrefix];
        if (!formState) return;
        var blockIndex = formState.nextBlockIndex++;
        var tpl = document.getElementById(formPrefix + '_vendor_service_block_tpl');
        if (!tpl) return;
        var html = tpl.innerHTML
            .replace(/__BLOCK_INDEX__/g, String(blockIndex))
            .replace(/__BLOCK_NUM__/g, String(formState.$container.find('.av-service-block').length + 1));
        var $block = window.jQuery(html.trim());
        formState.$container.find('#' + formPrefix + '_vendor_services_blocks').append($block);
        var blockState = getBlockState(formState, blockIndex);
        if (restoreData) {
            blockState.service_id = restoreData.service_id ? parseInt(restoreData.service_id, 10) : null;
            (restoreData.sub_services || []).forEach(function (row) {
                var sid = String(row.sub_service_id || 0);
                if (parseInt(sid, 10) === 0) {
                    blockState.selectedServiceTags = (row.tags || []).slice();
                } else {
                    blockState.selectedSubMap[sid] = {
                        name: row.sub_service_name || '',
                        tags: (row.tags || []).slice()
                    };
                }
            });
            (restoreData.price_overrides || []).forEach(function (row) {
                var subKey = String(row.service_sub_service_id || 0);
                blockState.priceOverrides[subKey] = {
                    price_12hr: row.price_12hr,
                    price_24hr: row.price_24hr,
                    price_onetime: row.price_onetime
                };
            });
        }
        renderServiceOptions($block.find('.av-service-select'), formState, blockState.service_id);
        if (blockState.service_id) {
            refreshBlockUI(formState, blockIndex);
        }
        renumberBlocks(formState);
    }

    function renumberBlocks(formState) {
        formState.$container.find('.av-service-block').each(function (i) {
            window.jQuery(this).find('.card-header strong').html('<i class="fas fa-layer-group mr-1"></i> Service ' + (i + 1));
        });
    }

    function clearBlocks(formPrefix) {
        var formState = forms[formPrefix];
        if (!formState) return;
        formState.blocks = {};
        formState.nextBlockIndex = 0;
        formState.$container.find('#' + formPrefix + '_vendor_services_blocks').empty();
        window.jQuery('#' + formPrefix + '_vendor_services_json').val('[]');
    }

    function loadLocationServices(formPrefix, locationName, callback) {
        var formState = forms[formPrefix];
        if (!formState || !locationName) {
            if (formState) formState.locationServices = [];
            if (callback) callback();
            return;
        }
        window.jQuery.getJSON(servicesUrl, {
            location: locationName,
            provider_type: 'vendor'
        }).done(function (res) {
            formState.locationServices = (res && res.services) ? res.services : [];
            if (callback) callback();
        }).fail(function () {
            formState.locationServices = [];
            if (callback) callback();
        });
    }

    function initForm(formPrefix, locationSelectId) {
        if (forms[formPrefix]) return forms[formPrefix];
        var formState = {
            prefix: formPrefix,
            locationServices: [],
            blocks: {},
            nextBlockIndex: 0,
            $container: window.jQuery('#' + formPrefix + '_vendor_services_wrap')
        };
        forms[formPrefix] = formState;

        window.jQuery(document).on('click', '#' + formPrefix + '_add_vendor_service', function () {
            if (!formState.locationServices.length) {
                toastr.warning('Pehle location select karein.');
                return;
            }
            addBlock(formPrefix);
        });

        window.jQuery(document).on('change', '#' + formPrefix + '_vendor_services_wrap .av-service-select', function () {
            var blockIndex = window.jQuery(this).attr('data-block-index');
            onServiceChange(formState, blockIndex, window.jQuery(this).val());
            serializeForm(formPrefix);
        });

        window.jQuery(document).on('change', '#' + locationSelectId, function () {
            var loc = window.jQuery(this).find('option:selected').data('city-name') || window.jQuery(this).find('option:selected').text();
            loc = String(loc || '').trim();
            if (loc === 'Select Location' || loc === '') {
                formState.locationServices = [];
                clearBlocks(formPrefix);
                return;
            }
            loadLocationServices(formPrefix, loc, function () {
                clearBlocks(formPrefix);
                if (formState.locationServices.length) {
                    addBlock(formPrefix);
                } else {
                    toastr.info('Is location par koi vendor service configured nahi hai.');
                }
            });
        });

        window.jQuery(document).on('change', '#' + formPrefix + '_vendor_services_wrap .av-sub-add-select', function () {
            var $blockEl = window.jQuery(this).closest('.av-service-block');
            var blockIndex = $blockEl.attr('data-block-index');
            var blockState = getBlockState(formState, blockIndex);
            var subId = window.jQuery(this).val();
            if (!subId) return;
            var svc = serviceById(formState, blockState.service_id);
            var sub = (svc.sub_services || []).find(function (s) { return String(s.id) === String(subId); });
            if (!sub) return;
            blockState.selectedSubMap[String(sub.id)] = { name: sub.name, tags: [] };
            window.jQuery(this).val('');
            refreshBlockUI(formState, blockIndex);
            serializeForm(formPrefix);
        });

        window.jQuery(document).on('click', '#' + formPrefix + '_vendor_services_wrap .av-sub-remove-btn', function () {
            var $blockEl = window.jQuery(this).closest('.av-service-block');
            var blockIndex = $blockEl.attr('data-block-index');
            var blockState = getBlockState(formState, blockIndex);
            var sid = window.jQuery(this).closest('.av-sub-block').attr('data-sub-id');
            delete blockState.selectedSubMap[sid];
            delete blockState.priceOverrides[sid];
            refreshBlockUI(formState, blockIndex);
            serializeForm(formPrefix);
        });

        window.jQuery(document).on('change', '#' + formPrefix + '_vendor_services_wrap .av-sub-tag-cb', function () {
            var $blockEl = window.jQuery(this).closest('.av-service-block');
            var blockIndex = $blockEl.attr('data-block-index');
            var blockState = getBlockState(formState, blockIndex);
            var sid = window.jQuery(this).closest('.av-sub-block').attr('data-sub-id');
            var tags = [];
            $blockEl.find('.av-sub-block[data-sub-id="' + sid + '"] .av-sub-tag-cb:checked').each(function () {
                tags.push(window.jQuery(this).val());
            });
            if (blockState.selectedSubMap[sid]) blockState.selectedSubMap[sid].tags = tags;
            serializeForm(formPrefix);
        });

        window.jQuery(document).on('change', '#' + formPrefix + '_vendor_services_wrap .av-service-tag-cb', function () {
            var $blockEl = window.jQuery(this).closest('.av-service-block');
            var blockIndex = $blockEl.attr('data-block-index');
            var blockState = getBlockState(formState, blockIndex);
            blockState.selectedServiceTags = [];
            $blockEl.find('.av-service-tag-cb:checked').each(function () {
                blockState.selectedServiceTags.push(window.jQuery(this).val());
            });
            serializeForm(formPrefix);
        });

        window.jQuery(document).on('input', '#' + formPrefix + '_vendor_services_wrap .av-price-input', function () {
            var $blockEl = window.jQuery(this).closest('.av-service-block');
            var blockIndex = $blockEl.attr('data-block-index');
            var blockState = getBlockState(formState, blockIndex);
            var subId = String(window.jQuery(this).data('sub-id') || 0);
            var field = window.jQuery(this).data('field');
            if (!blockState.priceOverrides[subId]) blockState.priceOverrides[subId] = {};
            blockState.priceOverrides[subId][field] = window.jQuery(this).val();
            serializeForm(formPrefix);
        });

        window.jQuery(document).on('click', '#' + formPrefix + '_vendor_services_wrap .av-remove-service-block', function () {
            var $blockEl = window.jQuery(this).closest('.av-service-block');
            var blockIndex = $blockEl.attr('data-block-index');
            delete formState.blocks[blockIndex];
            $blockEl.remove();
            renumberBlocks(formState);
            serializeForm(formPrefix);
        });

        return formState;
    }

    function loadBlocks(formPrefix, locationName, blocks) {
        initForm(formPrefix, formPrefix + '_location');
        clearBlocks(formPrefix);
        loadLocationServices(formPrefix, locationName, function () {
            var fs = forms[formPrefix];
            if (!blocks || !blocks.length) {
                if (fs && fs.locationServices && fs.locationServices.length) {
                    addBlock(formPrefix);
                }
                return;
            }
            blocks.forEach(function (block) {
                addBlock(formPrefix, block);
            });
            serializeForm(formPrefix);
        });
    }

    return {
        initForm: initForm,
        clearBlocks: clearBlocks,
        loadBlocks: loadBlocks,
        serialize: serializeForm
    };
})();
</script>
