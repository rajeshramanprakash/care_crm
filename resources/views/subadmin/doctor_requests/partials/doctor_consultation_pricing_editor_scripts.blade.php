<script>
(function () {
    var MODE_LABELS = { online: 'Online', home_visit: 'Home visit', clinic_visit: 'Clinic visit' };

    function drConsultPricingApiUrl(params) {
        var base = '{{ rtrim(url("/api"), "/") }}';
        var url = base + '/public/doctor-registration/pricing';
        var q = [];
        Object.keys(params || {}).forEach(function (k) {
            var v = params[k];
            if (v === undefined || v === null || v === '') return;
            q.push(encodeURIComponent(k) + '=' + encodeURIComponent(String(v)));
        });
        return q.length ? (url + '?' + q.join('&')) : url;
    }

    function drConsultFindPricingItem(state, subId) {
        var target = parseInt(subId || 0, 10);
        for (var i = 0; i < (state || []).length; i++) {
            if (parseInt(state[i].sub_service_id || 0, 10) === target) {
                return state[i];
            }
        }
        return null;
    }

    function drConsultFormatLocPrice(admin) {
        if (!admin || admin.website_price === null || admin.website_price === undefined || admin.website_price === '') {
            return '—';
        }
        return '₹' + admin.website_price;
    }

    window.drConsultPricingFindItem = drConsultFindPricingItem;

    window.drConsultPricingFetchLocation = function (locationId, serviceId) {
        if (!locationId || !serviceId) {
            return Promise.resolve({});
        }
        return fetch(drConsultPricingApiUrl({ location_id: locationId, service_id: serviceId }), {
            headers: { Accept: 'application/json' }
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                return (res && res.success && res.data && res.data.prices) ? (res.data.prices || {}) : {};
            })
            .catch(function () { return {}; });
    };

    window.drConsultPricingApplyLocationHints = function ($scope, locationPricingBySub) {
        $scope.find('.dr-sub-pricing-loc-hint').each(function () {
            var $el = $(this);
            var mode = String($el.data('mode') || '');
            var subId = String($el.data('sub-id') || '0');
            var admin = ((locationPricingBySub || {})[subId] || {})[mode] || null;
            $el.text('Location: ' + drConsultFormatLocPrice(admin));
        });
    };

    window.drConsultPricingBuildBlock = function (opts) {
        opts = opts || {};
        var drId = parseInt(opts.drId || 0, 10);
        var subId = parseInt(opts.subId || 0, 10);
        var subKey = String(subId);
        var enabledModes = (opts.enabledModes || []).filter(function (m) {
            return MODE_LABELS[m];
        });
        var existing = drConsultFindPricingItem(opts.pricingState || [], subId);
        var locationPricingBySub = opts.locationPricingBySub || {};
        var scopeKey = String(opts.scopeKey || 'reg');
        var sizeClass = opts.inputSizeClass === 'default' ? '' : 'form-control-sm';

        var $pricing = $('<div class="dr-sub-pricing-block mt-2 pt-2 border-top"/>').attr('data-sub-id', subId);
        $pricing.append('<div class="small font-weight-bold mb-1">Consultation charges</div>');
        $pricing.append('<p class="small text-muted mb-2">Doctor charge = doctor rate. CareWeb charge = price on this doctor’s card. Blank = use location default.</p>');

        if (!enabledModes.length) {
            $pricing.append('<p class="small text-muted mb-0">No consultation modes enabled for this doctor.</p>');
            return $pricing;
        }

        enabledModes.forEach(function (modeKey) {
            var admin = (locationPricingBySub[subKey] || {})[modeKey] || null;
            var existingMode = (existing && existing.modes) ? (existing.modes[modeKey] || {}) : {};
            var docVal = existingMode.doctor_price != null && existingMode.doctor_price !== '' ? existingMode.doctor_price : '';
            var webVal = existingMode.website_price != null && existingMode.website_price !== '' ? existingMode.website_price : '';
            var docId = scopeKey + '_price_' + drId + '_doc_' + modeKey + '_' + subKey;
            var webId = scopeKey + '_price_' + drId + '_web_' + modeKey + '_' + subKey;

            var $row = $('<div class="form-row dr-sub-pricing-mode-row align-items-end mb-2"/>');
            $row.append(
                $('<div class="col-12 col-md-3 mb-1 mb-md-0"/>').append(
                    $('<span class="small font-weight-bold d-block"/>').text(MODE_LABELS[modeKey] || modeKey),
                    $('<span class="small text-muted d-block dr-sub-pricing-loc-hint"/>')
                        .attr('data-mode', modeKey)
                        .attr('data-sub-id', subKey)
                        .text('Location: ' + drConsultFormatLocPrice(admin))
                )
            );
            $row.append(
                $('<div class="col-6 col-md-4 mb-1 mb-md-0"/>').append(
                    $('<label class="small mb-0"/>').text('Doctor (₹)'),
                    $('<input type="number" min="0" step="0.01" placeholder="Optional"/>')
                        .addClass('form-control dr-consult-price-doc ' + sizeClass)
                        .attr('id', docId)
                        .attr('data-mode', modeKey)
                        .attr('data-sub-id', subKey)
                        .val(docVal !== '' ? docVal : '')
                )
            );
            $row.append(
                $('<div class="col-6 col-md-5 mb-1 mb-md-0"/>').append(
                    $('<label class="small mb-0"/>').text('CareWeb card (₹)'),
                    $('<input type="number" min="0" step="0.01" placeholder="Optional"/>')
                        .addClass('form-control dr-consult-price-web ' + sizeClass)
                        .attr('id', webId)
                        .attr('data-mode', modeKey)
                        .attr('data-sub-id', subKey)
                        .val(webVal !== '' ? webVal : '')
                )
            );
            $pricing.append($row);
        });

        return $pricing;
    };

    window.drConsultPricingCollect = function ($scope, serviceId, items, enabledModes) {
        var next = [];
        (items || []).forEach(function (it) {
            var row = {
                service_id: parseInt(serviceId || 0, 10),
                sub_service_id: parseInt(it.sub_service_id || 0, 10),
                sub_service_name: it.sub_service_name ? String(it.sub_service_name) : null,
                modes: {}
            };
            (enabledModes || []).forEach(function (modeKey) {
                var subKey = String(it.sub_service_id || 0);
                var $doc = $scope.find('.dr-consult-price-doc[data-mode="' + modeKey + '"][data-sub-id="' + subKey + '"]');
                var $web = $scope.find('.dr-consult-price-web[data-mode="' + modeKey + '"][data-sub-id="' + subKey + '"]');
                var docVal = $doc.length ? String($doc.val() || '').trim() : '';
                var webVal = $web.length ? String($web.val() || '').trim() : '';
                if (docVal === '' && webVal === '') {
                    return;
                }
                row.modes[modeKey] = { locked: false };
                if (docVal !== '') {
                    row.modes[modeKey].doctor_price = parseFloat(docVal);
                }
                if (webVal !== '') {
                    row.modes[modeKey].website_price = parseFloat(webVal);
                }
            });
            if (Object.keys(row.modes).length) {
                next.push(row);
            }
        });
        return next;
    };

    window.drConsultPricingItemsFromDom = function ($scope) {
        var items = [];
        $scope.find('.dr-sub-pricing-block').each(function () {
            var subId = parseInt($(this).attr('data-sub-id') || '0', 10);
            var name = $(this).closest('.border.rounded').find('> .small.font-weight-bold, > .font-weight-bold').first().text().trim();
            items.push({
                sub_service_id: subId,
                sub_service_name: name || (subId > 0 ? 'Sub-service' : 'Service')
            });
        });
        return items;
    };

    window.drConsultPricingModesFromDom = function ($scope) {
        var modes = [];
        $scope.find('.dr-consult-price-doc').each(function () {
            var m = String($(this).data('mode') || '');
            if (m && modes.indexOf(m) === -1) {
                modes.push(m);
            }
        });
        return modes;
    };

    window.drConsultPricingInitInline = function ($scope) {
        var $root = ($scope && $scope.length) ? $scope : $(document);
        $root.find('.dr-inline-pricing-subs').each(function () {
            var $subs = $(this);
            var serviceId = parseInt($subs.data('service-id') || '0', 10);
            var locationId = parseInt($subs.data('location-id') || '0', 10);
            if (!serviceId || !locationId) {
                return;
            }
            window.drConsultPricingFetchLocation(locationId, serviceId).then(function (prices) {
                window.drConsultPricingApplyLocationHints($subs, prices);
            });
        });
    };

    window.drConsultPricingRenderModalBlocks = function ($container, res) {
        $container.empty();
        var subs = Array.isArray(res.consultation_sub_services) ? res.consultation_sub_services : [];
        var modes = Array.isArray(res.modes) ? res.modes : [];
        var pricingState = Array.isArray(res.consultation_pricing) ? res.consultation_pricing : [];
        var serviceId = parseInt(res.service_id || 0, 10);
        var locationId = parseInt(res.location_id || 0, 10);
        var drId = parseInt(res.id || 0, 10);
        var items = subs.length
            ? subs.map(function (s) {
                return {
                    sub_service_id: parseInt(s.sub_service_id || 0, 10),
                    sub_service_name: String(s.sub_service_name || 'Sub-service')
                };
            }).filter(function (x) { return x.sub_service_id > 0; })
            : [{ sub_service_id: 0, sub_service_name: res.job_title || 'Service' }];

        if (!items.length) {
            $container.append('<p class="small text-muted mb-0">Add consultation service and sub-services in registration details first.</p>');
            return Promise.resolve();
        }

        return window.drConsultPricingFetchLocation(locationId, serviceId).then(function (locPrices) {
            items.forEach(function (it) {
                var $block = $('<div class="border rounded px-3 py-2 mb-2 bg-white"/>');
                if (it.sub_service_id > 0) {
                    $block.append($('<div class="font-weight-bold mb-1"/>').text(it.sub_service_name || 'Sub-service'));
                } else {
                    $block.append($('<div class="font-weight-bold mb-1"/>').text(String(res.job_title || 'Service')));
                }
                $block.append(window.drConsultPricingBuildBlock({
                    scopeKey: 'modal',
                    drId: drId,
                    subId: it.sub_service_id,
                    enabledModes: modes,
                    pricingState: pricingState,
                    locationPricingBySub: locPrices,
                    inputSizeClass: 'default'
                }));
                $container.append($block);
            });
        });
    };
})();

$(function () {
    if (typeof window.drConsultPricingInitInline === 'function') {
        window.drConsultPricingInitInline($(document));
    }
});

$(document).on('shown.bs.modal', '#doctorViewModal', function () {
    if (typeof window.drConsultPricingInitInline === 'function') {
        window.drConsultPricingInitInline($('#doctorViewModalBody'));
    }
});
</script>
