<style>
    .jp-tags-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
    @media (max-width: 640px) { .jp-tags-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .jp-tag-pill { display: block; margin: 0; cursor: pointer; position: relative; }
    .jp-tag-pill input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .jp-tag-pill-inner {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        min-height: 38px; padding: 7px 10px; border-radius: 8px; border: 2px solid #ea8a2b;
        background: #fff; font-size: 0.82rem; font-weight: 600;
    }
    .jp-tag-pill input:checked + .jp-tag-pill-inner {
        background: linear-gradient(135deg, #fe992e 0%, #e8892a 100%); color: #fff; border-color: #e8892a;
    }
    .jp-sub-block {
        border: 1px solid #e8e4df; border-radius: 10px; padding: 0.75rem; margin-bottom: 0.65rem; background: #faf9f7;
    }
    .jp-sub-block-head { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem; }
    .jp-sub-remove-btn {
        padding: 0.25rem 0.55rem; font-size: 0.75rem; border-radius: 5px;
        border: 1px solid #fecaca; background: #fff; color: #b91c1c;
    }
    .jp-sub-remove-btn:hover { background: #fef2f2; }
    .jp-price-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .jp-price-table th, .jp-price-table td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: middle; }
    .jp-price-table th { background: #f9fafb; font-weight: 700; }
    .jp-loc-default { display: block; font-size: 0.72rem; color: #888; margin-top: 2px; }
    .jp-price-input { max-width: 110px; }
    .jp-override-badge { font-size: 0.68rem; background: #fff7ed; color: #c2410c; border: 1px solid #fdba74; border-radius: 999px; padding: 1px 6px; margin-left: 4px; }
</style>
<script>
window.JobProcFreelancerServices = (function () {
    var servicesUrl = @json(route('register.location-provider-services'));
    var instances = {};

    function escHtml(t) {
        var d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    function fld(prefix, name) {
        return prefix ? prefix + name : name;
    }

    function jpById(id) {
        return window.jQuery('#' + id);
    }

    function buildTagPill(tag, inputClass, checked) {
        var $lbl = window.jQuery('<label class="jp-tag-pill"/>');
        var $cb = window.jQuery('<input type="checkbox"/>').addClass(inputClass).val(tag).prop('checked', checked);
        var $inner = window.jQuery('<span class="jp-tag-pill-inner"/>').text(tag);
        return $lbl.append($cb).append($inner);
    }

    function init(prefix) {
        if (instances[prefix]) return instances[prefix];

        var state = {
            prefix: prefix,
            locationServices: [],
            selectedSubMap: {},
            selectedServiceTags: [],
            priceOverrides: {},
            pendingRestore: null
        };
        instances[prefix] = state;

        var cityId = fld(prefix, 'city');
        var jobId = fld(prefix, 'job_title');
        var shiftId = fld(prefix, 'shift');

        function getSelectedServiceRow() {
            var name = jpById(jobId).val();
            if (!name) return null;
            for (var i = 0; i < state.locationServices.length; i++) {
                if (state.locationServices[i].name === name) return state.locationServices[i];
            }
            return null;
        }

        function defaultPrice(subId) {
            var svc = getSelectedServiceRow();
            if (!svc || !svc.prices) return { price_12hr: null, price_24hr: null, price_onetime: null };
            var key = String(subId || 0);
            return svc.prices[key] || svc.prices['0'] || { price_12hr: null, price_24hr: null, price_onetime: null };
        }

        function overrideKey(subId) {
            var svc = getSelectedServiceRow();
            if (!svc) return '';
            return String(svc.id) + '|' + String(subId || 0);
        }

        function getEffectivePrice(subId) {
            var key = overrideKey(subId);
            var def = defaultPrice(subId);
            var ov = state.priceOverrides[key] || {};
            return {
                price_12hr: ov.price_12hr != null && ov.price_12hr !== '' ? ov.price_12hr : def.price_12hr,
                price_24hr: ov.price_24hr != null && ov.price_24hr !== '' ? ov.price_24hr : def.price_24hr,
                price_onetime: ov.price_onetime != null && ov.price_onetime !== '' ? ov.price_onetime : def.price_onetime,
                defaults: def
            };
        }

        function syncHiddenFields() {
            var svc = getSelectedServiceRow();
            var subPayload = [];
            if (svc && (svc.sub_services || []).length) {
                Object.keys(state.selectedSubMap).forEach(function (sid) {
                    var entry = state.selectedSubMap[sid];
                    if (!entry) return;
                    subPayload.push({
                        sub_service_id: parseInt(sid, 10),
                        sub_service_name: entry.name,
                        tags: entry.tags.slice()
                    });
                });
            } else if (svc && state.selectedServiceTags.length) {
                subPayload.push({
                    sub_service_id: 0,
                    sub_service_name: null,
                    tags: state.selectedServiceTags.slice()
                });
            }
            jpById(fld(prefix, 'service_sub_services_json')).val(JSON.stringify(subPayload));

            var pricePayload = [];
            Object.keys(state.priceOverrides).forEach(function (key) {
                var parts = key.split('|');
                var ov = state.priceOverrides[key];
                pricePayload.push({
                    service_id: parseInt(parts[0], 10),
                    service_sub_service_id: parseInt(parts[1], 10),
                    price_12hr: ov.price_12hr != null && ov.price_12hr !== '' ? ov.price_12hr : null,
                    price_24hr: ov.price_24hr != null && ov.price_24hr !== '' ? ov.price_24hr : null,
                    price_onetime: ov.price_onetime != null && ov.price_onetime !== '' ? ov.price_onetime : null
                });
            });
            jpById(fld(prefix, 'service_price_overrides_json')).val(JSON.stringify(pricePayload));
        }

        function renderSubServiceTagBlock(sub) {
            var sid = String(sub.id);
            var entry = state.selectedSubMap[sid] || { name: sub.name, tags: [] };
            state.selectedSubMap[sid] = entry;
            var $block = window.jQuery('<div class="jp-sub-block"/>').attr('data-sub-id', sid);
            var $head = window.jQuery('<div class="jp-sub-block-head"/>');
            $head.append(window.jQuery('<strong/>').text(sub.name));
            $head.append(window.jQuery('<button type="button" class="jp-sub-remove-btn"/>').html('<i class="fas fa-times"></i> Remove'));
            $block.append($head);
            var tags = sub.tags || [];
            if (tags.length) {
                $block.append('<small class="text-muted d-block mb-2">Tags select karein:</small>');
                var $grid = window.jQuery('<div class="jp-tags-grid"/>');
                tags.forEach(function (tag) {
                    $grid.append(buildTagPill(tag, 'jp-sub-tag-cb', entry.tags.indexOf(tag) !== -1));
                });
                $block.append($grid);
            }
            return $block;
        }

        function refreshSubServiceAddSelect() {
            var svc = getSelectedServiceRow();
            var $sel = jpById(fld(prefix, 'jp_sub_service_add_select')).empty().append('<option value="">Select sub-service to add</option>');
            if (!svc || !svc.sub_services) return;
            svc.sub_services.forEach(function (sub) {
                if (state.selectedSubMap[String(sub.id)]) return;
                $sel.append(window.jQuery('<option/>').val(sub.id).text(sub.name));
            });
        }

        function renderServiceTags(svc) {
            var $wrap = jpById(fld(prefix, 'jp_service_tags_wrap'));
            var $grid = jpById(fld(prefix, 'jp_service_tags_grid')).empty();
            var tags = svc.tags || [];
            if (!tags.length) { $wrap.hide(); return; }
            $wrap.show();
            tags.forEach(function (tag) {
                $grid.append(buildTagPill(tag, 'jp-service-tag-cb', state.selectedServiceTags.indexOf(tag) !== -1));
            });
        }

        function pricingItems() {
            var svc = getSelectedServiceRow();
            if (!svc) return [];
            var subs = svc.sub_services || [];
            if (subs.length) {
                return Object.keys(state.selectedSubMap).map(function (sid) {
                    return { sub_service_id: parseInt(sid, 10), label: state.selectedSubMap[sid].name };
                });
            }
            return [{ sub_service_id: 0, label: svc.name }];
        }

        function renderPricePanel() {
            var items = pricingItems();
            var $wrap = jpById(fld(prefix, 'jp_price_panel_wrap'));
            var $table = jpById(fld(prefix, 'jp_price_table_wrap')).empty();
            var shift = jpById(shiftId).val();
            if (!items.length || !shift) { $wrap.hide(); return; }
            $wrap.show();

            var show12 = shift === '12' || shift === 'both';
            var show24 = shift === '24' || shift === 'both';
            var showOnce = shift === 'onetime';

            var html = '<table class="jp-price-table"><thead><tr><th>Service / Sub-service</th>';
            if (show12) html += '<th>12hr (₹)</th>';
            if (show24) html += '<th>24hr (₹)</th>';
            if (showOnce) html += '<th>One-time (₹)</th>';
            html += '</tr></thead><tbody>';

            items.forEach(function (it) {
                var eff = getEffectivePrice(it.sub_service_id);
                var key = overrideKey(it.sub_service_id);
                html += '<tr data-price-key="' + escHtml(key) + '"><td>' + escHtml(it.label) + '</td>';
                if (show12) html += priceCellHtml('price_12hr', key, eff);
                if (show24) html += priceCellHtml('price_24hr', key, eff);
                if (showOnce) html += priceCellHtml('price_onetime', key, eff);
                html += '</tr>';
            });
            html += '</tbody></table>';
            $table.html(html);
        }

        function priceCellHtml(field, key, eff) {
            var val = eff[field];
            var def = eff.defaults[field];
            var ov = state.priceOverrides[key] || {};
            var inputVal = ov[field] != null && ov[field] !== '' ? ov[field] : (val != null && val !== '' ? val : '');
            var isOverride = ov[field] != null && ov[field] !== '' && String(ov[field]) !== String(def != null ? def : '');
            var badge = isOverride ? '<span class="jp-override-badge">Custom</span>' : '';
            var defHint = def != null && def !== '' ? '<span class="jp-loc-default">Location: ₹' + def + '</span>' : '<span class="jp-loc-default">Location: —</span>';
            return '<td>' + badge +
                '<input type="number" min="0" step="0.01" class="form-control form-control-sm jp-price-input jp-price-field" data-key="' + escHtml(key) + '" data-field="' + field + '" value="' + escHtml(String(inputVal)) + '">' +
                defHint + '</td>';
        }

        function resetServiceSelection() {
            state.selectedSubMap = {};
            state.selectedServiceTags = [];
            state.priceOverrides = {};
            jpById(fld(prefix, 'jp_sub_service_tags_stack')).empty();
            jpById(fld(prefix, 'jp_sub_service_picker_wrap')).hide();
            jpById(fld(prefix, 'jp_service_tags_wrap')).hide();
            jpById(fld(prefix, 'jp_price_panel_wrap')).hide();
            jpById(fld(prefix, 'jp_price_table_wrap')).empty();
            syncHiddenFields();
        }

        function populateJobTitleOptions() {
            var $job = jpById(jobId);
            var current = $job.val();
            $job.empty().append('<option value="">Select Job Title</option>');
            state.locationServices.forEach(function (s) {
                $job.append(window.jQuery('<option/>').val(s.name).text(s.name));
            });
            if (current) $job.val(current);
        }

        function loadLocationServices(callback) {
            var location = jpById(cityId).val();
            resetServiceSelection();
            jpById(fld(prefix, 'jp_service_section')).hide();

            if (!location) {
                jpById(jobId).prop('disabled', true).empty().append('<option value="">Pehle location select karein</option>');
                if (callback) callback();
                return;
            }

            jpById(jobId).prop('disabled', true).empty().append('<option value="">Loading…</option>');

            window.jQuery.get(servicesUrl, { location: location, provider_type: 'freelancer' })
                .done(function (res) {
                    state.locationServices = (res && res.success && res.services) ? res.services : [];
                    populateJobTitleOptions();
                    if (!state.locationServices.length) {
                        jpById(jobId).append('<option value="" disabled>Is location par koi service nahi</option>');
                    }
                    jpById(jobId).prop('disabled', false);
                    if (callback) callback();
                })
                .fail(function () {
                    jpById(jobId).empty().append('<option value="">Load failed</option>');
                    jpById(jobId).prop('disabled', false);
                    if (callback) callback();
                });
        }

        function onJobTitleChange() {
            resetServiceSelection();
            var svc = getSelectedServiceRow();
            if (!svc) {
                jpById(fld(prefix, 'jp_service_section')).hide();
                return;
            }
            jpById(fld(prefix, 'jp_service_section')).show();
            var subs = svc.sub_services || [];
            if (subs.length) {
                jpById(fld(prefix, 'jp_sub_service_picker_wrap')).show();
                jpById(fld(prefix, 'jp_service_tags_wrap')).hide();
                refreshSubServiceAddSelect();
            } else {
                jpById(fld(prefix, 'jp_sub_service_picker_wrap')).hide();
                renderServiceTags(svc);
            }
            renderPricePanel();
        }

        function restoreFromData(data) {
            if (!data) return;
            state.pendingRestore = data;
            var subs = data.service_sub_services || [];
            var overrides = data.service_price_overrides || [];
            overrides.forEach(function (row) {
                var key = String(row.service_id) + '|' + String(row.service_sub_service_id || 0);
                state.priceOverrides[key] = {
                    price_12hr: row.price_12hr,
                    price_24hr: row.price_24hr,
                    price_onetime: row.price_onetime
                };
            });

            loadLocationServices(function () {
                if (data.job_title) {
                    jpById(jobId).val(data.job_title);
                    onJobTitleChange();
                }
                var svc = getSelectedServiceRow();
                if (!svc) return;

                if ((svc.sub_services || []).length) {
                    subs.forEach(function (entry) {
                        var sid = String(entry.sub_service_id);
                        if (parseInt(sid, 10) <= 0) return;
                        var sub = (svc.sub_services || []).find(function (s) { return String(s.id) === sid; });
                        if (!sub) return;
                        state.selectedSubMap[sid] = {
                            name: entry.sub_service_name || sub.name,
                            tags: (entry.tags || []).slice()
                        };
                        jpById(fld(prefix, 'jp_sub_service_tags_stack')).append(renderSubServiceTagBlock(sub));
                        var block = jpById(fld(prefix, 'jp_sub_service_tags_stack')).find('.jp-sub-block[data-sub-id="' + sid + '"]');
                        (entry.tags || []).forEach(function (tag) {
                            block.find('.jp-sub-tag-cb[value="' + tag.replace(/"/g, '\\"') + '"]').prop('checked', true);
                        });
                    });
                    refreshSubServiceAddSelect();
                } else {
                    state.selectedServiceTags = (subs[0] && subs[0].tags) ? subs[0].tags.slice() : [];
                    renderServiceTags(svc);
                    jpById(fld(prefix, 'jp_service_tags_grid')).find('.jp-service-tag-cb').each(function () {
                        window.jQuery(this).prop('checked', state.selectedServiceTags.indexOf(String(window.jQuery(this).val())) !== -1);
                    });
                }
                renderPricePanel();
                syncHiddenFields();
            });
        }

        function validateSelection() {
            var location = jpById(cityId).val();
            if (!location) return 'Location select karein.';
            var svc = getSelectedServiceRow();
            if (!svc) return 'Job title select karein.';
            var subs = svc.sub_services || [];
            if (subs.length) {
                if (!Object.keys(state.selectedSubMap).length) return 'Kam se kam ek sub-service select karein.';
                var keys = Object.keys(state.selectedSubMap);
                for (var i = 0; i < keys.length; i++) {
                    var entry = state.selectedSubMap[keys[i]];
                    var sub = subs.find(function (s) { return String(s.id) === keys[i]; });
                    var allowed = (sub && sub.tags) ? sub.tags : [];
                    if (allowed.length && (!entry.tags || !entry.tags.length)) {
                        return 'Tags required for: ' + entry.name;
                    }
                }
            } else if ((svc.tags || []).length && !state.selectedServiceTags.length) {
                return 'Is service ke liye kam se kam ek tag select karein.';
            }
            return '';
        }

        // Events — scoped by prefix container
        var sectionSel = '#' + fld(prefix, 'jp_service_section');

        jpById(cityId).on('change.jpSvc' + prefix, function () {
            loadLocationServices();
        });

        jpById(jobId).on('change.jpSvc' + prefix, onJobTitleChange);
        jpById(shiftId).on('change.jpSvc' + prefix, renderPricePanel);

        window.jQuery(document).on('change', '#' + fld(prefix, 'jp_sub_service_add_select'), function () {
            var sid = window.jQuery(this).val();
            if (!sid) return;
            var svc = getSelectedServiceRow();
            if (!svc) return;
            var sub = (svc.sub_services || []).find(function (s) { return String(s.id) === String(sid); });
            if (!sub || state.selectedSubMap[String(sid)]) return;
            jpById(fld(prefix, 'jp_sub_service_tags_stack')).append(renderSubServiceTagBlock(sub));
            refreshSubServiceAddSelect();
            window.jQuery(this).val('');
            syncHiddenFields();
            renderPricePanel();
        });

        window.jQuery(document).on('click', sectionSel + ' .jp-sub-remove-btn', function () {
            var $block = window.jQuery(this).closest('.jp-sub-block');
            var sid = $block.attr('data-sub-id');
            delete state.selectedSubMap[sid];
            delete state.priceOverrides[overrideKey(parseInt(sid, 10))];
            $block.remove();
            refreshSubServiceAddSelect();
            syncHiddenFields();
            renderPricePanel();
        });

        window.jQuery(document).on('change', sectionSel + ' .jp-sub-tag-cb', function () {
            var $block = window.jQuery(this).closest('.jp-sub-block');
            var sid = $block.attr('data-sub-id');
            if (!state.selectedSubMap[sid]) return;
            var tags = [];
            $block.find('.jp-sub-tag-cb:checked').each(function () { tags.push(String(window.jQuery(this).val())); });
            state.selectedSubMap[sid].tags = tags;
            syncHiddenFields();
        });

        window.jQuery(document).on('change', sectionSel + ' .jp-service-tag-cb', function () {
            state.selectedServiceTags = [];
            jpById(fld(prefix, 'jp_service_tags_grid')).find('.jp-service-tag-cb:checked').each(function () {
                state.selectedServiceTags.push(String(window.jQuery(this).val()));
            });
            syncHiddenFields();
        });

        window.jQuery(document).on('input', sectionSel + ' .jp-price-field', function () {
            var key = window.jQuery(this).data('key');
            var field = window.jQuery(this).data('field');
            if (!state.priceOverrides[key]) state.priceOverrides[key] = {};
            state.priceOverrides[key][field] = window.jQuery(this).val();
            syncHiddenFields();
        });

        state.loadLocationServices = loadLocationServices;
        state.restoreFromData = restoreFromData;
        state.validateSelection = validateSelection;
        state.reset = resetServiceSelection;

        return state;
    }

    return { init: init };
})();
</script>
