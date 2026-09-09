(function ($) {
    'use strict';

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatLocationOption(state) {
        if (!state.id) {
            return $('<span class="loc-opt-placeholder">' + escapeHtml(state.text) + '</span>');
        }

        var $option = $(state.element);
        var city = $.trim($option.data('cityName') || state.text || $option.val() || '');
        var st = $.trim($option.data('state') || '');

        if (!st) {
            return $('<span class="loc-opt-row"><span class="loc-opt-city">' + escapeHtml(city) + '</span></span>');
        }

        return $(
            '<span class="loc-opt-row">' +
                '<span class="loc-opt-city">' + escapeHtml(city) + '</span>' +
                '<span class="loc-opt-state">' + escapeHtml(st) + '</span>' +
            '</span>'
        );
    }

    function buildLocationSelectOptions() {
        return {
            width: '100%',
            allowClear: true,
            templateResult: formatLocationOption,
            templateSelection: formatLocationOption,
            escapeMarkup: function (markup) {
                return markup;
            }
        };
    }

    window.initLocationSelects = function (context) {
        if (typeof $.fn.select2 === 'undefined') {
            return;
        }

        var $scope = context ? $(context) : $(document);

        $scope.find('select.location-select').each(function () {
            var $el = $(this);

            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }

            var placeholderOption = $el.find('option[value=""]').first();
            var placeholder = $el.data('placeholder') || (placeholderOption.length ? placeholderOption.text() : 'Select location');
            var opts = buildLocationSelectOptions();

            opts.placeholder = placeholder;

            if ($el.prop('required') || $el.data('allow-clear') === false) {
                opts.allowClear = false;
            }

            var $modal = $el.closest('.modal');
            if ($modal.length) {
                opts.dropdownParent = $modal;
            }

            $el.select2(opts);

            var $container = $el.next('.select2-container');
            $container.addClass('location-select2');

            if ($el.hasClass('form-control')) {
                $el.addClass('location-select-native-hidden');
            }

            $el.off('select2:open.locationSearchFocus').on('select2:open.locationSearchFocus', function () {
                var attempts = 0;
                var focusSearch = function () {
                    var $field = $('.select2-container--open .select2-search__field').filter(':visible').last();
                    if ($field.length) {
                        $field.trigger('focus');
                        try { $field[0].focus({ preventScroll: true }); } catch (e) { $field[0].focus(); }
                        return;
                    }
                    if (attempts++ < 8) {
                        window.setTimeout(focusSearch, 25);
                    }
                };
                window.setTimeout(focusSearch, 0);
            });
        });
    };

    $(document).off('select2:open.locationSearchFocusGlobal').on('select2:open.locationSearchFocusGlobal', function () {
        window.setTimeout(function () {
            var field = document.querySelector('.select2-container--open .select2-search__field');
            if (field) {
                try { field.focus({ preventScroll: true }); } catch (e) { field.focus(); }
            }
        }, 0);
    });

    window.buildLocationOptionHtml = function (city, state, selected, value) {
        var val = value != null ? value : city;
        var sel = selected ? ' selected' : '';
        var st = state ? String(state).replace(/"/g, '&quot;') : '';
        var c = String(city).replace(/"/g, '&quot;');
        return '<option value="' + escapeHtml(val) + '" data-state="' + st + '" data-city-name="' + c + '"' + sel + '>' + escapeHtml(city) + '</option>';
    };

    $(document).ready(function () {
        initLocationSelects();
    });
})(jQuery);
