/**
 * Multi status remarks — history, AI polish (sales/manager), admin edit.
 */
(function (window, $) {
    'use strict';

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function ensureRemarksCss() {
        if (document.getElementById('clsr-remarks-css')) {
            return;
        }
        var cssUrl = window.__leadStatusRemarksAssets && window.__leadStatusRemarksAssets.cssUrl;
        if (!cssUrl) {
            return;
        }
        var link = document.createElement('link');
        link.id = 'clsr-remarks-css';
        link.rel = 'stylesheet';
        link.href = cssUrl;
        document.head.appendChild(link);
    }

    function initialsFromName(name) {
        var parts = String(name || 'Unknown').trim().split(/\s+/);
        var a = (parts[0] || 'U').charAt(0);
        var b = parts.length > 1 ? parts[1].charAt(0) : '';
        return (a + b).toUpperCase() || 'U';
    }

    function buildRemarkBodyHtml(r, options) {
        options = options || {};
        if (options.adminCanEdit && r.can_edit) {
            return '<textarea class="form-control form-control-sm admin-remark-edit" rows="3" data-remark-id="' + r.id + '">'
                + escapeHtml(r.remark) + '</textarea>'
                + '<button type="button" class="btn btn-sm btn-primary save-lead-remark-btn" data-remark-id="' + r.id + '">'
                + '<i class="fas fa-save"></i> Save changes</button>';
        }
        var isAi = r.is_ai_polished && r.original_remark && String(r.original_remark).trim() !== '';
        if (isAi) {
            return '<div class="clsr-remark-blocks">'
                + '<div class="clsr-remark-block clsr-remark-block--english">'
                + '<span class="clsr-remark-block__label"><i class="fas fa-language"></i> English</span>'
                + '<p class="clsr-remark-text">' + escapeHtml(r.remark) + '</p></div>'
                + '<div class="clsr-remark-block clsr-remark-block--draft">'
                + '<span class="clsr-remark-block__label"><i class="fas fa-pen-fancy"></i> Draft notes</span>'
                + '<p class="clsr-remark-text clsr-remark-text--draft">' + escapeHtml(r.original_remark) + '</p></div>'
                + '</div>';
        }
        return '<p class="clsr-remark-text">' + escapeHtml(r.remark) + '</p>';
    }

    function buildRemarkItemHtml(r, options) {
        options = options || {};
        var compact = options.sidebar ? ' clsr-timeline-item--compact' : '';
        var compactCard = options.sidebar ? ' clsr-remark-card--compact' : '';
        var name = r.created_by_name || 'Unknown';
        var status = r.status_at_remark ? '<span class="clsr-status-chip">' + escapeHtml(r.status_at_remark) + '</span>' : '';
        var editClass = (options.adminCanEdit && r.can_edit) ? ' clsr-remark-card--admin-edit' : '';
        return '<div class="clsr-timeline-item' + compact + '" data-remark-id="' + (r.id || '') + '">'
            + '<div class="clsr-timeline-marker" aria-hidden="true"></div>'
            + '<article class="clsr-remark-card' + editClass + compactCard + '">'
            + '<header class="clsr-remark-card__head">'
            + '<div class="clsr-remark-card__who">'
            + '<span class="clsr-avatar" title="' + escapeHtml(name) + '">' + escapeHtml(initialsFromName(name)) + '</span>'
            + '<div class="clsr-who-text"><span class="clsr-name">' + escapeHtml(name) + '</span>'
            + '<time class="clsr-date">' + escapeHtml(r.created_at || '—') + '</time></div></div>'
            + status
            + '</header><div class="clsr-remark-card__body">' + buildRemarkBodyHtml(r, options) + '</div>'
            + '</article></div>';
    }

    function buildRemarksPanelHtml(remarks, options) {
        options = options || {};
        var title = options.panelTitle || 'Sales / Manager Status Updates';
        var count = remarks.length;
        var countLabel = options.sidebar ? String(count) : (count === 1 ? '1 update' : count + ' updates');
        var readOnlyPill = options.readOnly !== false && !options.adminCanEdit
            ? '<span class="clsr-pill">Read only</span>' : '';
        var panelClass = 'clsr-panel' + (options.sidebar ? ' clsr-panel--sidebar' : '');
        var items = remarks.map(function (r) {
            return buildRemarkItemHtml(r, options);
        }).join('');
        return '<section class="' + panelClass + '" aria-label="' + escapeHtml(title) + '">'
            + '<header class="clsr-panel__head"><h4 class="clsr-panel__title">'
            + '<i class="fas fa-history" aria-hidden="true"></i>' + escapeHtml(title) + '</h4>'
            + '<div class="clsr-panel__meta"><span class="clsr-pill clsr-pill--count">' + countLabel + '</span>'
            + readOnlyPill + '</div></header>'
            + '<div class="clsr-panel__body clsr-panel__body--scroll"><div class="clsr-timeline">' + items + '</div></div></section>';
    }

    function renderHistory(containerSelector, remarks, options) {
        options = options || {};
        ensureRemarksCss();
        var $c = $(containerSelector);
        $c.empty();
        if (!remarks || !remarks.length) {
            $c.html('<p class="text-muted small mb-0">No status remarks yet.</p>');
            return;
        }
        if (!options.panelTitle) {
            options.panelTitle = options.adminCanEdit ? 'Saved remarks' : (options.operationOwn ? 'Operation Status Updates' : 'Sales / Manager Status');
        }
        if (options.adminCanEdit) {
            options.readOnly = false;
        }
        if (options.compactSidebar) {
            options.sidebar = true;
        }
        var wrap = options.sidebar
            ? '<div class="clsr-inline-wrap">' + buildRemarksPanelHtml(remarks, options) + '</div>'
            : buildRemarksPanelHtml(remarks, options);
        $c.html(wrap);

        if (options.adminCanEdit && options.leadId) {
            $c.find('.save-lead-remark-btn').off('click').on('click', function () {
                var remarkId = $(this).data('remark-id');
                var $ta = $c.find('.admin-remark-edit[data-remark-id="' + remarkId + '"]');
                var text = ($ta.val() || '').trim();
                if (!text) {
                    alert('Remark cannot be empty.');
                    return;
                }
                var $btn = $(this);
                $btn.prop('disabled', true);
                $.ajax({
                    url: '/admin/leads/' + options.leadId + '/status-remarks/' + remarkId,
                    method: 'PUT',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        remark: text
                    },
                    success: function () {
                        $btn.prop('disabled', false);
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Remark updated');
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        alert((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to update remark');
                    }
                });
            });
        }
    }

    function resetAiFields(prefix) {
        $('#' + prefix + '_new_status_remark').val('');
        $('#' + prefix + '_new_status_remark_original').val('');
        $('#' + prefix + '_status_remark_ai_token').val('');
        $('#' + prefix + '_status_remark_ai_confirmed').val('0');
        $('#' + prefix + '_new_status_remark_preview').val('');
        $('#' + prefix + '_ai_status_hint').text('Optional — polish your notes into English before saving.');
    }

    function prepareForSave(prefix) {
        var $draft = $('#' + prefix + '_new_status_remark_draft');
        if (!$draft.length) {
            return;
        }
        var draft = ($draft.val() || '').trim();
        var polished = ($('#' + prefix + '_new_status_remark').val() || '').trim();
        var confirmed = $('#' + prefix + '_status_remark_ai_confirmed').val() === '1';
        if (polished === '' && draft !== '') {
            $('#' + prefix + '_new_status_remark').val(draft);
            if (!confirmed) {
                $('#' + prefix + '_new_status_remark_original').val('');
                $('#' + prefix + '_status_remark_ai_token').val('');
            }
        }
    }

    function clearNew(prefix) {
        if ($('#' + prefix + '_new_status_remark_draft').length) {
            $('#' + prefix + '_new_status_remark_draft').val('');
            resetAiFields(prefix);
        } else {
            $('#' + prefix + '_new_status_remark').val('');
        }
    }

    function resolveStatusForRemarks(prefix) {
        var v = $('#' + prefix + '_status').val();
        if (v) {
            return v;
        }
        var $modal = $('#editOperationLeadModal');
        if ($modal.length) {
            var s = $modal.find('select[name="status"]').val();
            if (s) {
                return s;
            }
        }
        return '';
    }

    function aiUrlForLead(prefix, leadId) {
        var $g = $('#' + prefix + '_status_remarks_group');
        var tpl = $g.data('ai-url-template');
        if (tpl && String(tpl).indexOf('__ID__') !== -1) {
            return String(tpl).replace('__ID__', leadId);
        }
        var path = window.location.pathname || '';
        if (path.indexOf('/operation-manager') !== -1) {
            return '/operation-manager/operation-leads/' + leadId + '/status-remark/generate-ai';
        }
        if (path.indexOf('/operation') !== -1) {
            return '/operation/operation-leads/' + leadId + '/status-remark/generate-ai';
        }
        if (path.indexOf('/manager/') === 0) {
            return '/manager/leads/' + leadId + '/status-remark/generate-ai';
        }
        return '/sales/leads/' + leadId + '/status-remark/generate-ai';
    }

    function bindAiGenerate(prefix) {
        var $btn = $('#' + prefix + '_generate_status_remark_ai');
        if (!$btn.length || $btn.data('bound')) {
            return;
        }
        $btn.data('bound', true);

        $('#' + prefix + '_new_status_remark_draft').on('input', function () {
            resetAiFields(prefix);
        });

        $btn.on('click', function () {
            var leadId = $('#edit_lead_id').val() || window._leadStatusRemarksLeadId;
            if (!leadId) {
                alert('Lead not loaded. Open the lead edit form first.');
                return;
            }
            var draft = ($('#' + prefix + '_new_status_remark_draft').val() || '').trim();
            if (draft.length < 3) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Type your status notes first (at least a few words).');
                } else {
                    alert('Type your status notes first.');
                }
                return;
            }
            var status = resolveStatusForRemarks(prefix);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating…');
            $.ajax({
                url: aiUrlForLead(prefix, leadId),
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draft: draft,
                    status: status
                },
                success: function (res) {
                    if (res.success && res.remark) {
                        $('#' + prefix + '_new_status_remark_preview').val(res.remark);
                        $('#' + prefix + '_new_status_remark').val(res.remark);
                        $('#' + prefix + '_new_status_remark_original').val(res.original_remark || draft);
                        $('#' + prefix + '_status_remark_ai_token').val(res.ai_token || '');
                        $('#' + prefix + '_status_remark_ai_confirmed').val('1');
                        $('#' + prefix + '_ai_status_hint').text('Ready to save with this remark.');
                        if (typeof toastr !== 'undefined') {
                            toastr.success(res.message || 'English translation ready — check and save.');
                        }
                    } else {
                        var msg = res.message || 'Could not generate';
                        if (typeof toastr !== 'undefined') {
                            toastr.error(msg);
                        } else {
                            alert(msg);
                        }
                    }
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Request failed';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(msg);
                    } else {
                        alert(msg);
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false).html('<i class="fas fa-magic"></i> Generate with AI');
                }
            });
        });
    }

    /**
     * Call before lead update AJAX. Returns false if blocked.
     */
    function validateBeforeSave(prefix) {
        prepareForSave(prefix);
        var $g = $('#' + prefix + '_status_remarks_group');
        if ($g.data('require-ai') !== 1 && $g.data('require-ai') !== '1') {
            return true;
        }
        var draft = ($('#' + prefix + '_new_status_remark_draft').val() || '').trim();
        var polished = ($('#' + prefix + '_new_status_remark').val() || '').trim();
        var confirmed = $('#' + prefix + '_status_remark_ai_confirmed').val() === '1';

        if (draft === '' && polished === '') {
            return true;
        }
        if (draft !== '' && (!confirmed || polished === '')) {
            var msg = 'Please click "Generate with AI" on your status remark before saving.';
            if (typeof toastr !== 'undefined') {
                toastr.warning(msg);
            } else {
                alert(msg);
            }
            return false;
        }
        return true;
    }

    function onEditLeadLoaded(prefix, response, options) {
        options = options || {};
        window._leadStatusRemarksLeadId = response.id;
        if (options.operationOwn) {
            options.readOnly = false;
            options.panelTitle = options.panelTitle || 'Operation Status Updates';
        }
        renderHistory('#' + prefix + '_status_remarks_history', response.status_remarks_list || [], options);
        clearNew(prefix);
        bindAiGenerate(prefix);
        if (response.status) {
            $('#' + prefix + '_status_remarks_group').show();
        }
    }

    function onViewLeadLoaded(prefix, response) {
        var $group = $('#' + prefix + '_status_remarks_group');
        var list = response.status_remarks_list || [];
        if (list.length) {
            $group.show();
            $group.find('label.fw-bold').first().hide();
            renderHistory('#' + prefix + '_status_remarks_history', list, {
                readOnly: true,
                compactSidebar: true,
                panelTitle: 'Sales / Manager Status'
            });
        } else if (response.status_remarks && String(response.status_remarks).trim() !== '') {
            $group.show();
            $('#' + prefix + '_status_remarks_history').html(
                '<div class="border rounded p-2 bg-white"><div style="white-space:pre-wrap;">' +
                escapeHtml(response.status_remarks) + '</div></div>'
            );
        } else {
            $group.hide();
        }
    }

    /**
     * Read-only block for Operation / Operation Manager edit modals (no edit controls).
     */
    function renderOperationReadOnlyBlock(remarks) {
        if (!remarks || !remarks.length) {
            return '';
        }
        ensureRemarksCss();
        return "<div class='col-12 col-md-5 col-lg-4 ms-md-auto mb-2 clsr-modal-sidebar'>"
            + buildRemarksPanelHtml(remarks, {
                readOnly: true,
                sidebar: true,
                panelTitle: 'Sales / Manager Status'
            }) + '</div>';
    }

    window.LeadStatusRemarks = {
        renderHistory: renderHistory,
        renderOperationReadOnlyBlock: renderOperationReadOnlyBlock,
        clearNew: clearNew,
        onEditLeadLoaded: onEditLeadLoaded,
        onViewLeadLoaded: onViewLeadLoaded,
        validateBeforeSave: validateBeforeSave,
        prepareForSave: prepareForSave,
        bindAiGenerate: bindAiGenerate,
        resetAiFields: resetAiFields
    };

    $(function () {
        $('[data-field-prefix][data-require-ai="1"]').each(function () {
            bindAiGenerate($(this).data('field-prefix'));
        });
    });
})(window, jQuery);
