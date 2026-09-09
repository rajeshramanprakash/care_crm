<script>
(function () {
  var providerType = @json($registrationProviderType ?? 'vendor');
  var servicesUrl = @json(route('register.location-provider-services'));

  var locationServices = [];
  var selectedSubMap = {};
  var selectedServiceTags = [];

  function escHtml(t) {
    var d = document.createElement('div');
    d.textContent = t;
    return d.innerHTML;
  }

  function getSelectedServiceRow() {
    var name = $('#job_title').val();
    if (!name) return null;
    for (var i = 0; i < locationServices.length; i++) {
      if (locationServices[i].name === name) return locationServices[i];
    }
    return null;
  }

  function syncHiddenSubServices() {
    var svc = getSelectedServiceRow();
    var payload = [];
    if (svc && (svc.sub_services || []).length) {
      Object.keys(selectedSubMap).forEach(function (sid) {
        var entry = selectedSubMap[sid];
        if (!entry) return;
        payload.push({
          sub_service_id: parseInt(sid, 10),
          sub_service_name: entry.name,
          tags: entry.tags.slice()
        });
      });
    } else if (svc && selectedServiceTags.length) {
      payload.push({
        sub_service_id: 0,
        sub_service_name: null,
        tags: selectedServiceTags.slice()
      });
    }
    $('#service_sub_services_json').val(JSON.stringify(payload));
    renderSubSummary();
  }

  function renderSubSummary() {
    var $wrap = $('#provider_sub_service_summary_wrap');
    var $list = $('#provider_sub_service_summary_list').empty();
    var svc = getSelectedServiceRow();
    var hasSubs = svc && (svc.sub_services || []).length;
    if (!hasSubs && selectedServiceTags.length) {
      $wrap.show();
      $list.append('<div class="provider-summary-row"><strong>' + escHtml(svc.name) + '</strong><div class="provider-summary-tags">' +
        selectedServiceTags.map(function (t) { return '<span class="provider-tag-chip">' + escHtml(t) + '</span>'; }).join('') +
        '</div></div>');
      return;
    }
    var keys = Object.keys(selectedSubMap);
    if (!keys.length) {
      $wrap.hide();
      return;
    }
    $wrap.show();
    keys.forEach(function (sid) {
      var entry = selectedSubMap[sid];
      var tagsHtml = (entry.tags || []).map(function (t) {
        return '<span class="provider-tag-chip">' + escHtml(t) + '</span>';
      }).join('');
      $list.append('<div class="provider-summary-row"><strong>' + escHtml(entry.name) + '</strong><div class="provider-summary-tags">' + tagsHtml + '</div></div>');
    });
  }

  function buildTagPill(tag, inputClass, checked) {
    var $lbl = $('<label class="provider-tag-pill"/>');
    var $cb = $('<input type="checkbox"/>').addClass(inputClass).val(tag).prop('checked', checked);
    var $inner = $('<span class="provider-tag-pill-inner"/>');
    $inner.append($('<span class="provider-tag-text"/>').text(tag));
    $inner.append($('<span class="provider-tag-icon"/>'));
    $lbl.append($cb).append($inner);
    return $lbl;
  }

  function renderServiceTags(svc) {
    var $wrap = $('#provider_service_tags_wrap');
    var $grid = $('#provider_service_tags_grid').empty();
    var tags = svc.tags || [];
    if (!tags.length) {
      $wrap.hide();
      return;
    }
    $wrap.show();
    tags.forEach(function (tag) {
      var checked = selectedServiceTags.indexOf(tag) !== -1;
      $grid.append(buildTagPill(tag, 'provider-service-tag-cb', checked));
    });
  }

  function renderSubServiceTagBlock(sub) {
    var sid = String(sub.id);
    var entry = selectedSubMap[sid] || { name: sub.name, tags: [] };
    selectedSubMap[sid] = entry;
    var $block = $('<div class="provider-sub-block"/>').attr('data-sub-id', sid);
    var $head = $('<div class="provider-sub-block-head"/>');
    $head.append($('<strong/>').text(sub.name));
    $head.append($('<button type="button" class="provider-sub-remove-btn"/>').html('<i class="fas fa-times"></i> Remove'));
    $block.append($head);
    var tags = sub.tags || [];
    if (!tags.length) {
      $block.append('<p class="provider-help mb-0">No tags have been set by admin for this sub-service.</p>');
    } else {
      $block.append('<p class="provider-help mb-2">Select tags for this sub-service:</p>');
      var $grid = $('<div class="provider-tags-grid"/>');
      tags.forEach(function (tag) {
        var checked = entry.tags.indexOf(tag) !== -1;
        $grid.append(buildTagPill(tag, 'provider-sub-tag-cb', checked));
      });
      $block.append($grid);
    }
    return $block;
  }

  function refreshSubServiceAddSelect() {
    var svc = getSelectedServiceRow();
    var $sel = $('#provider_sub_service_add_select').empty().append('<option value="">Select sub-service to add</option>');
    if (!svc || !svc.sub_services) return;
    svc.sub_services.forEach(function (sub) {
      if (selectedSubMap[String(sub.id)]) return;
      $sel.append($('<option/>').val(sub.id).text(sub.name));
    });
  }

  function resetServiceSelection() {
    selectedSubMap = {};
    selectedServiceTags = [];
    $('#provider_sub_service_tags_stack').empty();
    $('#provider_sub_service_picker_wrap').hide();
    $('#provider_service_tags_wrap').hide();
    $('#provider_sub_service_summary_wrap').hide();
    $('#provider_price_panel_wrap').hide();
    $('#provider_price_table_wrap').empty();
    syncHiddenSubServices();
  }

  function loadLocationServices() {
    var location = $('#location').val();
    var $job = $('#job_title');
    resetServiceSelection();
    $job.empty().append('<option value="">Select Job Title</option>');
    $('#provider_service_picker_wrap').hide();

    if (!location) {
      $job.prop('disabled', true).append('<option value="">Select location first</option>');
      return;
    }

    $job.prop('disabled', true).empty().append('<option value="">Loading services…</option>');

    $.get(servicesUrl, { location: location, provider_type: providerType })
      .done(function (res) {
        locationServices = (res && res.success && res.services) ? res.services : [];
        $job.empty().append('<option value="">Select Job Title</option>');
        if (!locationServices.length) {
          $job.append('<option value="" disabled>No services configured for this location</option>');
          return;
        }
        locationServices.forEach(function (s) {
          $job.append($('<option/>').val(s.name).text(s.name));
        });
        $job.prop('disabled', false);
      })
      .fail(function () {
        $job.empty().append('<option value="">Could not load services</option>');
        if (window.toastr) toastr.error('Could not load services. Please select the location again.');
      });
  }

  function onJobTitleChange() {
    resetServiceSelection();
    var svc = getSelectedServiceRow();
    if (!svc) {
      $('#provider_service_picker_wrap').hide();
      return;
    }
    $('#provider_service_picker_wrap').show();
    var subs = svc.sub_services || [];
    if (subs.length) {
      $('#provider_sub_service_picker_wrap').show();
      $('#provider_service_tags_wrap').hide();
      refreshSubServiceAddSelect();
    } else {
      $('#provider_sub_service_picker_wrap').hide();
      renderServiceTags(svc);
    }
    renderPricePanel();
    if (typeof window.syncFreelancerNurseDeclaration === 'function') {
      window.syncFreelancerNurseDeclaration();
    }
  }

  function priceForItem(subId) {
    var svc = getSelectedServiceRow();
    if (!svc || !svc.prices) return null;
    var key = String(subId || 0);
    return svc.prices[key] || svc.prices['0'] || null;
  }

  function pricingItems() {
    var svc = getSelectedServiceRow();
    if (!svc) return [];
    var subs = svc.sub_services || [];
    if (subs.length) {
      return Object.keys(selectedSubMap).map(function (sid) {
        return {
          sub_service_id: parseInt(sid, 10),
          sub_service_name: selectedSubMap[sid].name
        };
      });
    }
    return [{ sub_service_id: 0, sub_service_name: svc.name }];
  }

  function renderPricePanel() {
    var shift = $('#shift').val();
    var items = pricingItems();
    var $wrap = $('#provider_price_panel_wrap');
    var $table = $('#provider_price_table_wrap').empty();
    var svc = getSelectedServiceRow();
    var needsRadius = providerType === 'freelancer';
    var prev12 = $('#radius_12hr_km').val() || '';
    var prev24 = $('#radius_24hr_km').val() || '';
    var prevOnce = $('#radius_onetime_km').val() || '';

    if (!svc || !shift || !items.length) {
      $wrap.hide();
      return;
    }

    $wrap.show();
    var show12 = shift === '12' || shift === 'both';
    var show24 = shift === '24' || shift === 'both';
    var showOnce = shift === 'onetime' || (!show12 && !show24);

    var html = '<table class="provider-price-table"><thead><tr><th>Service / Sub-service</th>';
    if (show12) html += '<th>12hr (₹)</th>';
    if (show24) html += '<th>24hr (₹)</th>';
    if (showOnce) html += '<th>One-time (₹)</th>';
    html += '</tr></thead><tbody>';

    items.forEach(function (it) {
      var p = priceForItem(it.sub_service_id);
      var label = it.sub_service_id ? it.sub_service_name : (it.sub_service_name || svc.name);
      html += '<tr><td>' + escHtml(label) + '</td>';
      if (show12) {
        html += '<td><span class="provider-price-amount">' +
          (p && p.price_12hr != null && p.price_12hr !== '' ? '₹' + p.price_12hr : '—') +
          '</span></td>';
      }
      if (show24) {
        html += '<td><span class="provider-price-amount">' +
          (p && p.price_24hr != null && p.price_24hr !== '' ? '₹' + p.price_24hr : '—') +
          '</span></td>';
      }
      if (showOnce) {
        html += '<td><span class="provider-price-amount">' +
          (p && p.price_onetime != null && p.price_onetime !== '' ? '₹' + p.price_onetime : '—') +
          '</span></td>';
      }
      html += '</tr>';
    });

    if (needsRadius) {
      html += '<tr class="provider-radius-row"><td><strong>Work radius (km)</strong><div class="provider-help mb-0 mt-1">How far you can work</div></td>';
      if (show12) {
        html += '<td><label class="provider-radius-label" for="radius_12hr_km">12hr radius *</label>' +
          '<input type="number" class="provider-radius-input" name="radius_12hr_km" id="radius_12hr_km" min="0.1" step="0.1" max="5000" required placeholder="e.g. 10" value="' + escHtml(prev12) + '"></td>';
      }
      if (show24) {
        html += '<td><label class="provider-radius-label" for="radius_24hr_km">24hr radius *</label>' +
          '<input type="number" class="provider-radius-input" name="radius_24hr_km" id="radius_24hr_km" min="0.1" step="0.1" max="5000" required placeholder="e.g. 15" value="' + escHtml(prev24) + '"></td>';
      }
      if (showOnce) {
        html += '<td><label class="provider-radius-label" for="radius_onetime_km">One-time radius *</label>' +
          '<input type="number" class="provider-radius-input" name="radius_onetime_km" id="radius_onetime_km" min="0.1" step="0.1" max="5000" required placeholder="e.g. 8" value="' + escHtml(prevOnce) + '"></td>';
      }
      html += '</tr>';
    }

    html += '</tbody></table>';
    $table.html(html);
  }

  function validateProviderSelection() {
    var location = $('#location').val();
    if (!location) return 'Please select a location first.';
    var svc = getSelectedServiceRow();
    if (!svc) return 'Please select a job title.';
    var subs = svc.sub_services || [];
    if (subs.length) {
      if (!Object.keys(selectedSubMap).length) return 'Please select at least one sub-service.';
      var keys = Object.keys(selectedSubMap);
      for (var i = 0; i < keys.length; i++) {
        var entry = selectedSubMap[keys[i]];
        var sub = subs.find(function (s) { return String(s.id) === keys[i]; });
        var allowed = (sub && sub.tags) ? sub.tags : [];
        if (allowed.length && (!entry.tags || !entry.tags.length)) {
          return 'Please select at least one tag for each sub-service: ' + entry.name;
        }
      }
    } else if ((svc.tags || []).length && !selectedServiceTags.length) {
      return 'Please select at least one tag for this service.';
    }
    var shift = $('#shift').val();
    if (!shift) return 'Please select 12/24 hr or One-time.';

    if (providerType === 'freelancer') {
      var need12 = shift === '12' || shift === 'both';
      var need24 = shift === '24' || shift === 'both';
      var needOnce = shift === 'onetime';
      if (need12) {
        var r12 = parseFloat($('#radius_12hr_km').val());
        if (!isFinite(r12) || r12 <= 0) return 'Please enter work radius (km) for 12hr.';
      }
      if (need24) {
        var r24 = parseFloat($('#radius_24hr_km').val());
        if (!isFinite(r24) || r24 <= 0) return 'Please enter work radius (km) for 24hr.';
      }
      if (needOnce) {
        var rOnce = parseFloat($('#radius_onetime_km').val());
        if (!isFinite(rOnce) || rOnce <= 0) return 'Please enter work radius (km) for One-time.';
      }
    }
    return '';
  }

  window.validateProviderRegistrationSelection = validateProviderSelection;

  $('#location').on('change', loadLocationServices);
  $('#job_title').on('change', onJobTitleChange);
  $('#shift').on('change', renderPricePanel);

  $('#provider_sub_service_add_select').on('change', function () {
    var sid = $(this).val();
    if (!sid) return;
    var svc = getSelectedServiceRow();
    if (!svc) return;
    var sub = (svc.sub_services || []).find(function (s) { return String(s.id) === String(sid); });
    if (!sub) return;
    if (selectedSubMap[String(sid)]) return;
    $('#provider_sub_service_tags_stack').append(renderSubServiceTagBlock(sub));
    refreshSubServiceAddSelect();
    $(this).val('');
    syncHiddenSubServices();
    renderPricePanel();
  });

  $(document).on('click', '.provider-sub-remove-btn', function () {
    var $block = $(this).closest('.provider-sub-block');
    var sid = $block.attr('data-sub-id');
    delete selectedSubMap[sid];
    $block.remove();
    refreshSubServiceAddSelect();
    syncHiddenSubServices();
    renderPricePanel();
  });

  $(document).on('change', '.provider-sub-tag-cb', function () {
    var $block = $(this).closest('.provider-sub-block');
    var sid = $block.attr('data-sub-id');
    if (!selectedSubMap[sid]) return;
    var tags = [];
    $block.find('.provider-sub-tag-cb:checked').each(function () {
      tags.push(String($(this).val()));
    });
    selectedSubMap[sid].tags = tags;
    syncHiddenSubServices();
  });

  $(document).on('change', '#provider_service_tags_grid input[type="checkbox"]', function () {
    selectedServiceTags = [];
    $('#provider_service_tags_grid input.provider-service-tag-cb:checked').each(function () {
      selectedServiceTags.push(String($(this).val()));
    });
    syncHiddenSubServices();
    renderPricePanel();
  });

  // Initial state
  $('#job_title').prop('disabled', true);
  if ($('#location').val()) {
    loadLocationServices();
  }
})();
</script>
