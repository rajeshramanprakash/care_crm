<script>
(function () {
    if (window._drRegProfileEditorScriptsBound) {
        return;
    }
    window._drRegProfileEditorScriptsBound = true;

    function drRegCsrf() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function drRegCollectEdu($w) {
        var rows = [];
        $w.find('.dr-reg-edu-body tr.dr-reg-edu-row:not(.dr-reg-edu-template)').each(function () {
            var $r = $(this);
            rows.push({
                degree: ($r.find('.dr-reg-edu-degree').val() || '').trim(),
                institution: ($r.find('.dr-reg-edu-inst').val() || '').trim(),
                year_completed: ($r.find('.dr-reg-edu-year').val() || '').trim()
            });
        });
        return rows;
    }

    function drRegCollectExp($w) {
        var rows = [];
        $w.find('.dr-reg-exp-body tr.dr-reg-exp-row:not(.dr-reg-exp-template)').each(function () {
            var $r = $(this);
            rows.push({
                title: ($r.find('.dr-reg-exp-title').val() || '').trim(),
                organization: ($r.find('.dr-reg-exp-org').val() || '').trim(),
                from_year: ($r.find('.dr-reg-exp-from').val() || '').trim(),
                to_year: ($r.find('.dr-reg-exp-to').val() || '').trim(),
                details: ($r.find('.dr-reg-exp-details').val() || '').trim()
            });
        });
        return rows;
    }

    function drRegCollectSpecs($w) {
        var out = [];
        $w.find('.dr-reg-tag-pill.is-selected').each(function () {
            var t = String($(this).attr('data-tag') || '').trim();
            if (t !== '') {
                out.push(t);
            }
        });
        return out;
    }

    $(document).on('click', '.dr-reg-tag-pill', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var on = $btn.hasClass('is-selected');
        $btn.toggleClass('is-selected', !on);
        $btn.attr('aria-pressed', on ? 'false' : 'true');
    });

    $(document).on('click', '.dr-reg-lang-pill', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var on = $btn.hasClass('is-selected');
        $btn.toggleClass('is-selected', !on);
        $btn.attr('aria-pressed', on ? 'false' : 'true');
    });

    function drRegCollectLangs($w) {
        var out = [];
        $w.find('.dr-reg-lang-pill.is-selected').each(function () {
            var k = String($(this).attr('data-lang') || '').trim();
            if (k !== '') {
                out.push(k);
            }
        });
        return out;
    }

    function drRegReloadCard(id) {
        var $body = $('#doctorViewModalBody');
        if ($body.length) {
            $.ajax({
                url: '{{ url('subadmin/doctor-requests') }}/' + id + '/view-modal',
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (html) {
                $body.html(html);
                if (typeof window.drRegInitAllServiceEditors === 'function') {
                    window.drRegInitAllServiceEditors($body);
                }
                if (typeof window.drRegInitAllModesEditors === 'function') {
                    window.drRegInitAllModesEditors($body);
                }
                if (typeof window.drConsultPricingInitInline === 'function') {
                    window.drConsultPricingInitInline($body);
                }
            });
        } else {
            window.location.reload();
        }
    }

    $(document).on('click', '.dr-reg-edu-add', function (e) {
        e.preventDefault();
        var $w = $(this).closest('.dr-reg-profile-wrap');
        var $tpl = $w.find('tr.dr-reg-edu-template').first();
        var $n = $tpl.clone(true, true);
        $n.removeClass('dr-reg-edu-template d-none').addClass('dr-reg-edu-row').removeAttr('aria-hidden');
        $n.find('input').val('');
        $w.find('.dr-reg-edu-body').append($n);
    });

    $(document).on('click', '.dr-reg-edu-rm', function (e) {
        e.preventDefault();
        var $tr = $(this).closest('tr');
        var $tb = $tr.closest('tbody');
        var $rows = $tb.find('tr.dr-reg-edu-row:not(.dr-reg-edu-template)');
        if ($rows.length <= 1) {
            $tr.find('input').val('');
            return;
        }
        $tr.remove();
    });

    $(document).on('click', '.dr-reg-exp-add', function (e) {
        e.preventDefault();
        var $w = $(this).closest('.dr-reg-profile-wrap');
        var $tpl = $w.find('tr.dr-reg-exp-template').first();
        var $n = $tpl.clone(true, true);
        $n.removeClass('dr-reg-exp-template d-none').addClass('dr-reg-exp-row').removeAttr('aria-hidden');
        $n.find('input').val('');
        $w.find('.dr-reg-exp-body').append($n);
    });

    $(document).on('click', '.dr-reg-exp-rm', function (e) {
        e.preventDefault();
        var $tr = $(this).closest('tr');
        var $tb = $tr.closest('tbody');
        var $rows = $tb.find('tr.dr-reg-exp-row:not(.dr-reg-exp-template)');
        if ($rows.length <= 1) {
            $tr.find('input').val('');
            return;
        }
        $tr.remove();
    });

    $(document).on('click', '.dr-reg-city-save', function () {
        var $w = $(this).closest('.dr-reg-city-wrap');
        var id = parseInt($w.data('dr-id'), 10) || 0;
        var url = $w.data('url');
        var $msg = $w.find('.dr-reg-city-msg');
        var cityVal = ($w.find('.dr-reg-city').val() || '').trim();
        if (!url || !id) {
            return;
        }
        if (!cityVal) {
            toastr.error('Please select a city.');
            $msg.text('Please select a city.').addClass('text-danger').removeClass('text-success');
            return;
        }
        $msg.text('Saving…').removeClass('text-danger text-success');
        $.ajax({
            url: url,
            method: 'PUT',
            data: JSON.stringify({
                filters_only: true,
                city: cityVal
            }),
            contentType: 'application/json; charset=UTF-8',
            headers: {
                'X-CSRF-TOKEN': drRegCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            },
            success: function (res) {
                if (res && res.success) {
                    toastr.success(res.message || 'City saved');
                    $msg.text('Saved.').addClass('text-success');
                    drRegReloadCard(id);
                } else {
                    toastr.error((res && res.message) ? res.message : 'Failed');
                    $msg.text('').addClass('text-danger');
                }
            },
            error: function (xhr) {
                var m =
                    xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Request failed';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    m = Object.values(xhr.responseJSON.errors)
                        .map(function (a) {
                            return a.join(' ');
                        })
                        .join(' ');
                }
                toastr.error(m);
                $msg.text(m).addClass('text-danger');
            }
        });
    });

    $(document).on('click', '.dr-reg-filters-save', function () {
        var $w = $(this).closest('.dr-reg-filters-wrap');
        var id = parseInt($w.data('dr-id'), 10) || 0;
        var url = $w.data('url');
        var $msg = $w.find('.dr-reg-filters-msg');
        if (!url || !id) {
            return;
        }
        $msg.text('Saving…').removeClass('text-danger text-success');
        $.ajax({
            url: url,
            method: 'PUT',
            data: JSON.stringify({
                filters_only: true,
                gender: $w.find('.dr-reg-gender').val() || '',
                fluent_languages: drRegCollectLangs($w)
            }),
            contentType: 'application/json; charset=UTF-8',
            headers: {
                'X-CSRF-TOKEN': drRegCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            },
            success: function (res) {
                if (res && res.success) {
                    toastr.success(res.message || 'Saved');
                    $msg.text('Saved.').addClass('text-success');
                    drRegReloadCard(id);
                } else {
                    toastr.error((res && res.message) ? res.message : 'Failed');
                    $msg.text('').addClass('text-danger');
                }
            },
            error: function (xhr) {
                var m =
                    xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Request failed';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    m = Object.values(xhr.responseJSON.errors)
                        .map(function (a) {
                            return a.join(' ');
                        })
                        .join(' ');
                }
                toastr.error(m);
                $msg.text(m).addClass('text-danger');
            }
        });
    });

    $(document).on('click', '.dr-reg-profile-save', function () {
        var $w = $(this).closest('.dr-reg-profile-wrap');
        var id = parseInt($w.data('dr-id'), 10) || 0;
        var url = $w.data('url');
        var $msg = $w.find('.dr-reg-profile-msg');
        if (!url || !id) {
            return;
        }
        $msg.text('Saving…').removeClass('text-danger text-success');
        $.ajax({
            url: url,
            method: 'PUT',
            data: JSON.stringify({
                education_history: drRegCollectEdu($w),
                experience_history: drRegCollectExp($w)
            }),
            contentType: 'application/json; charset=UTF-8',
            headers: {
                'X-CSRF-TOKEN': drRegCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            },
            success: function (res) {
                if (res && res.success) {
                    toastr.success(res.message || 'Saved');
                    $msg.text('Saved.').addClass('text-success');
                    drRegReloadCard(id);
                } else {
                    toastr.error((res && res.message) ? res.message : 'Failed');
                    $msg.text('').addClass('text-danger');
                }
            },
            error: function (xhr) {
                var m =
                    xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Request failed';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    m = Object.values(xhr.responseJSON.errors)
                        .map(function (a) {
                            return a.join(' ');
                        })
                        .join(' ');
                }
                toastr.error(m);
                $msg.text(m).addClass('text-danger');
            }
        });
    });

    $(document).on('click', '.dr-reg-about-generate', function () {
        var $w = $(this).closest('.dr-reg-about-wrap');
        var url = $w.data('generate-url');
        var $msg = $w.find('.dr-reg-about-msg');
        var $ta = $w.find('.dr-reg-about-text');
        var details = ($ta.val() || '').trim();
        if (!url) {
            return;
        }
        var $btn = $w.find('.dr-reg-about-generate');
        $btn.prop('disabled', true);
        $msg.text('Generating…').removeClass('text-danger text-success');
        $.ajax({
            url: url,
            method: 'POST',
            data: JSON.stringify({ about_details: details }),
            contentType: 'application/json; charset=UTF-8',
            headers: {
                'X-CSRF-TOKEN': drRegCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            },
            success: function (res) {
                if (res && res.success && res.about_text) {
                    $ta.val(res.about_text);
                    toastr.success(res.message || 'About generated — review and save.');
                    $msg.text('Generated. Click Save About to store.').addClass('text-success');
                } else {
                    toastr.error((res && res.message) ? res.message : 'Could not generate');
                    $msg.text('').addClass('text-danger');
                }
            },
            error: function (xhr) {
                var m =
                    (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.about_details && xhr.responseJSON.errors.about_details[0]) ||
                    (xhr.responseJSON && xhr.responseJSON.message) ||
                    'Request failed';
                toastr.error(m);
                $msg.text(m).addClass('text-danger');
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.dr-reg-about-save', function () {
        var $w = $(this).closest('.dr-reg-about-wrap');
        var url = $w.data('save-url');
        var id = parseInt($w.data('dr-id'), 10) || 0;
        var $msg = $w.find('.dr-reg-about-msg');
        if (!url || !id) {
            return;
        }
        $msg.text('Saving…').removeClass('text-danger text-success');
        $.ajax({
            url: url,
            method: 'PUT',
            data: JSON.stringify({
                about_only: true,
                about_text: $w.find('.dr-reg-about-text').val() || ''
            }),
            contentType: 'application/json; charset=UTF-8',
            headers: {
                'X-CSRF-TOKEN': drRegCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            },
            success: function (res) {
                if (res && res.success) {
                    toastr.success(res.message || 'About saved');
                    $msg.text('Saved.').addClass('text-success');
                    drRegReloadCard(id);
                } else {
                    toastr.error((res && res.message) ? res.message : 'Failed');
                    $msg.text('').addClass('text-danger');
                }
            },
            error: function (xhr) {
                var m =
                    xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Request failed';
                toastr.error(m);
                $msg.text(m).addClass('text-danger');
            }
        });
    });

    if (typeof initLocationSelects === 'function') {
        initLocationSelects();
    }

    function drRegParseJsonAttr($el, key, fallback) {
        try {
            var raw = $el.attr(key);
            if (!raw) return fallback;
            return JSON.parse(raw);
        } catch (e) {
            return fallback;
        }
    }

    function drRegFindServiceInCatalog(catalog, jobTitle) {
        var name = String(jobTitle || '').trim().toLowerCase();
        if (!name) return null;
        for (var i = 0; i < catalog.length; i++) {
            if (String(catalog[i].name || '').trim().toLowerCase() === name) {
                return catalog[i];
            }
        }
        return null;
    }

    function drRegInitServiceEditor($w) {
        if (!$w.length || $w.data('drRegServiceInit')) {
            return;
        }
        $w.data('drRegServiceInit', true);

        var catalog = drRegParseJsonAttr($w, 'data-catalog', []);
        var selectedSubServices = {};
        var existingSubs = drRegParseJsonAttr($w, 'data-existing-subs', []);
        var existingTags = drRegParseJsonAttr($w, 'data-existing-tags', []);
        var consultationModes = drRegParseJsonAttr($w, 'data-consultation-modes', []);
        var drRegPricingState = drRegParseJsonAttr($w, 'data-consultation-pricing', []);
        var drRegLocationId = parseInt($w.attr('data-location-id') || '0', 10) || 0;
        var drRegLocationName = String($w.attr('data-location-name') || '').trim();
        var drRegAdminPricingBySub = {};
        var drRegPricingAbort = null;

        existingSubs.forEach(function (row) {
            var sid = String(row.sub_service_id || '');
            if (!sid || sid === '0') return;
            selectedSubServices[sid] = {
                sub_service_id: parseInt(sid, 10),
                sub_service_name: String(row.sub_service_name || ''),
                tags: Array.isArray(row.tags) ? row.tags.slice() : []
            };
        });

        function getSelectedServiceRow() {
            var jobTitle = String($w.find('.dr-reg-service-select').val() || '').trim();
            return drRegFindServiceInCatalog(catalog, jobTitle);
        }

        function renderServiceTags(svc) {
            var $wrap = $w.find('.dr-reg-service-tags-wrap');
            var $grid = $w.find('.dr-reg-service-tags-grid').empty();
            if (!svc || (svc.sub_services || []).length) {
                $wrap.hide();
                return;
            }
            var tags = svc.tags || [];
            if (!tags.length) {
                $wrap.hide();
                return;
            }
            $wrap.show();
            tags.forEach(function (tag) {
                var on = existingTags.indexOf(tag) !== -1;
                $grid.append(
                    $('<button type="button" class="dr-reg-tag-pill"/>')
                        .toggleClass('is-selected', on)
                        .attr('data-tag', tag)
                        .attr('aria-pressed', on ? 'true' : 'false')
                        .text(tag)
                );
            });
        }

        function syncSubTagsFromDom() {
            $w.find('.dr-reg-sub-block').each(function () {
                var sid = String($(this).data('sub-id') || '');
                if (!sid || !selectedSubServices[sid]) return;
                var tags = [];
                $(this).find('.dr-reg-tag-pill.is-selected').each(function () {
                    var t = String($(this).attr('data-tag') || '').trim();
                    if (t) tags.push(t);
                });
                selectedSubServices[sid].tags = tags;
            });
        }

        function drRegEnabledModes() {
            return ['online', 'home_visit', 'clinic_visit'].filter(drRegModeEnabled);
        }

        function drRegAppendPricingToBlock($block, subId, subName) {
            if (typeof window.drConsultPricingBuildBlock !== 'function') {
                return;
            }
            var svc = getSelectedServiceRow();
            if (!svc) {
                return;
            }
            $block.find('.dr-sub-pricing-block').remove();
            $block.append(window.drConsultPricingBuildBlock({
                scopeKey: 'reg',
                drId: parseInt($w.data('dr-id'), 10) || 0,
                subId: parseInt(subId || 0, 10),
                subName: subName,
                enabledModes: drRegEnabledModes(),
                pricingState: drRegPricingState,
                locationPricingBySub: drRegAdminPricingBySub,
                inputSizeClass: 'sm'
            }));
            $block.find('.dr-consult-price-doc, .dr-consult-price-web').on('input change', drRegSyncPricingFromInputs);
        }

        function drRegRenderServiceLevelPricing(svc) {
            var $slot = $w.find('.dr-reg-service-pricing-slot').empty();
            if (!svc || (svc.sub_services || []).length) {
                return;
            }
            if (typeof window.drConsultPricingBuildBlock !== 'function') {
                return;
            }
            $slot.append(window.drConsultPricingBuildBlock({
                scopeKey: 'reg',
                drId: parseInt($w.data('dr-id'), 10) || 0,
                subId: 0,
                subName: svc.name,
                enabledModes: drRegEnabledModes(),
                pricingState: drRegPricingState,
                locationPricingBySub: drRegAdminPricingBySub,
                inputSizeClass: 'sm'
            }));
            $slot.find('.dr-consult-price-doc, .dr-consult-price-web').on('input change', drRegSyncPricingFromInputs);
        }

        function renderSubBlock(sub) {
            var sid = String(sub.id);
            var entry = selectedSubServices[sid] || { tags: [] };
            var $block = $('<div class="dr-reg-sub-block"/>').attr('data-sub-id', sid);
            var $head = $('<div class="dr-reg-sub-block-head"/>');
            $head.append($('<strong/>').text(sub.name));
            $head.append(
                $('<button type="button" class="dr-reg-sub-remove"/>').text('Remove').on('click', function () {
                    syncSubTagsFromDom();
                    delete selectedSubServices[sid];
                    renderConsultationUi();
                })
            );
            $block.append($head);
            var tags = sub.tags || [];
            if (!tags.length) {
                $block.append('<p class="small text-muted mb-0">No tags configured for this sub-service in CRM.</p>');
            } else {
                var $grid = $('<div class="dr-reg-tags-grid"/>');
                tags.forEach(function (tag) {
                    var on = (entry.tags || []).indexOf(tag) !== -1;
                    $grid.append(
                        $('<button type="button" class="dr-reg-tag-pill"/>')
                            .toggleClass('is-selected', on)
                            .attr('data-tag', tag)
                            .attr('aria-pressed', on ? 'true' : 'false')
                            .text(tag)
                    );
                });
                $block.append($grid);
            }
            drRegAppendPricingToBlock($block, sid, sub.name);
            return $block;
        }

        function refreshSubAddDropdown(svc) {
            var $sel = $w.find('.dr-reg-sub-add').empty().append('<option value="">Select sub-service to add</option>');
            if (!svc || !(svc.sub_services || []).length) return;
            svc.sub_services.forEach(function (sub) {
                if (selectedSubServices[String(sub.id)]) return;
                $sel.append($('<option/>').val(String(sub.id)).text(sub.name));
            });
        }

        function drRegModeEnabled(modeKey) {
            return consultationModes.indexOf(modeKey) !== -1;
        }

        function drRegPricingApiUrl(path, params) {
            var base = '{{ rtrim(url("/api"), "/") }}';
            var url = base + '/' + String(path || '').replace(/^\/+/, '');
            var q = [];
            Object.keys(params || {}).forEach(function (k) {
                var v = params[k];
                if (v === undefined || v === null || v === '') return;
                q.push(encodeURIComponent(k) + '=' + encodeURIComponent(String(v)));
            });
            return q.length ? (url + '?' + q.join('&')) : url;
        }

        function drRegCurrentPricingItems(svc) {
            if (!svc) return [];
            var subs = svc.sub_services || [];
            if (subs.length) {
                return Object.keys(selectedSubServices).map(function (sid) {
                    var entry = selectedSubServices[sid] || {};
                    return {
                        sub_service_id: parseInt(String(sid), 10),
                        sub_service_name: entry.sub_service_name || 'Sub-service'
                    };
                }).filter(function (x) { return x.sub_service_id > 0; });
            }
            return [{ sub_service_id: 0, sub_service_name: null }];
        }

        function drRegFindPricingItem(subId) {
            var target = parseInt(subId || 0, 10);
            for (var i = 0; i < drRegPricingState.length; i++) {
                if (parseInt(drRegPricingState[i].sub_service_id || 0, 10) === target) {
                    return drRegPricingState[i];
                }
            }
            return null;
        }

        function drRegFetchLocationPricing(svc) {
            if (!svc || !svc.id || !drRegLocationId) {
                drRegAdminPricingBySub = {};
                return Promise.resolve(null);
            }
            if (drRegPricingAbort) drRegPricingAbort.abort();
            drRegPricingAbort = typeof AbortController !== 'undefined' ? new AbortController() : null;
            var signal = drRegPricingAbort ? drRegPricingAbort.signal : undefined;
            return fetch(drRegPricingApiUrl('public/doctor-registration/pricing', {
                location_id: drRegLocationId,
                service_id: svc.id
            }), {
                headers: { Accept: 'application/json' },
                signal: signal
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    drRegAdminPricingBySub = (res && res.success && res.data && res.data.prices) ? (res.data.prices || {}) : {};
                    return drRegAdminPricingBySub;
                })
                .catch(function (err) {
                    if (err && err.name === 'AbortError') return null;
                    drRegAdminPricingBySub = {};
                    return null;
                });
        }

        function drRegSyncPricingFromInputs() {
            var svc = getSelectedServiceRow();
            if (!svc || typeof window.drConsultPricingCollect !== 'function') {
                drRegPricingState = [];
                $w.data('drRegPricingState', drRegPricingState);
                return;
            }
            drRegPricingState = window.drConsultPricingCollect(
                $w,
                parseInt(svc.id, 10),
                drRegCurrentPricingItems(svc),
                drRegEnabledModes()
            );
            $w.data('drRegPricingState', drRegPricingState);
        }

        function renderConsultationUi() {
            var svc = getSelectedServiceRow();
            var $subPicker = $w.find('.dr-reg-sub-picker-wrap');
            var $blocks = $w.find('.dr-reg-sub-blocks').empty();
            $w.find('.dr-reg-service-pricing-slot').empty();
            if (!svc) {
                $subPicker.hide();
                $w.find('.dr-reg-service-tags-wrap').hide();
                return;
            }
            var subs = svc.sub_services || [];
            if (subs.length) {
                $subPicker.show();
                refreshSubAddDropdown(svc);
                Object.keys(selectedSubServices).forEach(function (sid) {
                    var sub = subs.find(function (s) { return String(s.id) === String(sid); });
                    if (sub) {
                        $blocks.append(renderSubBlock(sub));
                    }
                });
                $w.find('.dr-reg-service-tags-wrap').hide();
            } else {
                $subPicker.hide();
                renderServiceTags(svc);
            }

            drRegFetchLocationPricing(svc).finally(function () {
                if (subs.length) {
                    $blocks.find('.dr-reg-sub-block').each(function () {
                        var sid = String($(this).data('sub-id') || '');
                        var sub = subs.find(function (s) { return String(s.id) === sid; });
                        if (sub) {
                            drRegAppendPricingToBlock($(this), sid, sub.name);
                        }
                    });
                } else {
                    drRegRenderServiceLevelPricing(svc);
                }
            });
        }

        $w.find('.dr-reg-service-select').on('change', function () {
            selectedSubServices = {};
            existingTags = [];
            drRegPricingState = [];
            renderConsultationUi();
        });

        $w.find('.dr-reg-sub-add').on('change', function () {
            var sid = String($(this).val() || '');
            $(this).val('');
            if (!sid) return;
            var svc = getSelectedServiceRow();
            if (!svc) return;
            var sub = (svc.sub_services || []).find(function (s) { return String(s.id) === sid; });
            if (!sub) return;
            selectedSubServices[sid] = {
                sub_service_id: parseInt(sid, 10),
                sub_service_name: sub.name,
                tags: []
            };
            renderConsultationUi();
        });

        $w.data('drRegSyncPricing', drRegSyncPricingFromInputs);
        $w.data('drRegPricingState', drRegPricingState);

        renderConsultationUi();
    }

    function drRegCollectServicePayload($w) {
        var jobTitle = String($w.find('.dr-reg-service-select').val() || '').trim();
        var catalog = drRegParseJsonAttr($w, 'data-catalog', []);
        var svc = drRegFindServiceInCatalog(catalog, jobTitle);
        var subs = [];
        var specs = [];

        if (svc && (svc.sub_services || []).length) {
            $w.find('.dr-reg-sub-block').each(function () {
                var sid = String($(this).data('sub-id') || '');
                if (!sid) return;
                var tags = [];
                $(this).find('.dr-reg-tag-pill.is-selected').each(function () {
                    var t = String($(this).attr('data-tag') || '').trim();
                    if (t) tags.push(t);
                });
                var name = '';
                (svc.sub_services || []).forEach(function (sub) {
                    if (String(sub.id) === sid) name = sub.name;
                });
                subs.push({
                    sub_service_id: parseInt(sid, 10),
                    sub_service_name: name,
                    tags: tags
                });
            });
        } else if (svc) {
            $w.find('.dr-reg-service-tags-grid .dr-reg-tag-pill.is-selected').each(function () {
                var t = String($(this).attr('data-tag') || '').trim();
                if (t) specs.push(t);
            });
        }

        if (typeof $w.data('drRegSyncPricing') === 'function') {
            $w.data('drRegSyncPricing')();
        }

        return {
            job_title: jobTitle,
            consultation_sub_services: subs,
            specializations: specs,
            consultation_pricing: $w.data('drRegPricingState') || []
        };
    }

    $(document).on('click', '.dr-reg-service-save', function () {
        var $w = $(this).closest('.dr-reg-service-wrap');
        var id = parseInt($w.data('dr-id'), 10) || 0;
        var url = $w.data('url');
        var $msg = $w.find('.dr-reg-service-msg');
        if (!url || !id) return;

        var payload = drRegCollectServicePayload($w);
        if (!payload.job_title) {
            toastr.error('Please select a consultation service.');
            $msg.text('Please select a consultation service.').addClass('text-danger');
            return;
        }

        $msg.text('Saving…').removeClass('text-danger text-success');
        $.ajax({
            url: url,
            method: 'PUT',
            data: JSON.stringify({
                services_only: true,
                job_title: payload.job_title,
                consultation_sub_services: payload.consultation_sub_services,
                specializations: payload.specializations,
                consultation_pricing: payload.consultation_pricing
            }),
            contentType: 'application/json; charset=UTF-8',
            headers: {
                'X-CSRF-TOKEN': drRegCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            },
            success: function (res) {
                if (res && res.success) {
                    toastr.success(res.message || 'Saved');
                    $msg.text('Saved.').addClass('text-success');
                    drRegReloadCard(id);
                } else {
                    toastr.error((res && res.message) ? res.message : 'Failed');
                    $msg.text('').addClass('text-danger');
                }
            },
            error: function (xhr) {
                var m = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    m = Object.values(xhr.responseJSON.errors).map(function (a) { return a.join(' '); }).join(' ');
                }
                toastr.error(m);
                $msg.text(m).addClass('text-danger');
            }
        });
    });

    window.drRegInitAllServiceEditors = function ($scope) {
        var $root = ($scope && $scope.length) ? $scope : $(document);
        $root.find('.dr-reg-service-wrap').each(function () {
            drRegInitServiceEditor($(this));
        });
    };

    function drRegInitModesEditor($w) {
        if (!$w.length || $w.data('drRegModesInit')) {
            return;
        }
        $w.data('drRegModesInit', true);
    }

    window.drRegInitAllModesEditors = function ($scope) {
        var $root = ($scope && $scope.length) ? $scope : $(document);
        $root.find('.dr-reg-modes-wrap').each(function () {
            drRegInitModesEditor($(this));
        });
    };

    $(document).on('click', '.dr-reg-mode-pill', function () {
        var $btn = $(this);
        $btn.toggleClass('is-selected');
        $btn.attr('aria-pressed', $btn.hasClass('is-selected') ? 'true' : 'false');
    });

    $(document).on('click', '.dr-reg-modes-save', function () {
        var $w = $(this).closest('.dr-reg-modes-wrap');
        var id = parseInt($w.data('dr-id'), 10) || 0;
        var url = $w.data('url');
        var $msg = $w.find('.dr-reg-modes-msg');
        if (!url || !id) return;

        var modes = [];
        $w.find('.dr-reg-mode-pill.is-selected').each(function () {
            var m = String($(this).data('mode') || '').trim();
            if (m) modes.push(m);
        });
        if (!modes.length) {
            toastr.error('Select at least one consultation mode.');
            $msg.text('Select at least one consultation mode.').addClass('text-danger');
            return;
        }

        $msg.text('Saving…').removeClass('text-danger text-success');
        $.ajax({
            url: url,
            method: 'PUT',
            data: JSON.stringify({
                modes_only: true,
                consultation_modes: modes
            }),
            contentType: 'application/json; charset=UTF-8',
            headers: {
                'X-CSRF-TOKEN': drRegCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            },
            success: function (res) {
                if (res && res.success) {
                    toastr.success(res.message || 'Saved');
                    $msg.text('Saved.').addClass('text-success');
                    drRegReloadCard(id);
                } else {
                    toastr.error((res && res.message) ? res.message : 'Failed');
                    $msg.text('').addClass('text-danger');
                }
            },
            error: function (xhr) {
                var m = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    m = Object.values(xhr.responseJSON.errors).map(function (a) { return a.join(' '); }).join(' ');
                }
                toastr.error(m);
                $msg.text(m).addClass('text-danger');
            }
        });
    });

    $(document).on('shown.bs.modal', '#doctorViewModal', function () {
        window.drRegInitAllServiceEditors($('#doctorViewModalBody'));
        window.drRegInitAllModesEditors($('#doctorViewModalBody'));
    });

    window.drRegInitAllServiceEditors();
    window.drRegInitAllModesEditors();
})();
</script>
