<script>
(function () {
    if (window._drProfileImageAdminBound) {
        return;
    }
    window._drProfileImageAdminBound = true;

    function drProfileCsrf() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function drProfileSetMsg($el, text, isError) {
        if (!$el.length) {
            return;
        }
        $el.text(text || '').toggleClass('text-danger', !!isError).toggleClass('text-success', !!text && !isError);
    }

    $(document).on('click', '#drProfileGenerateBtn', function () {
        var $wrap = $(this).closest('.dr-profile-image-admin');
        var url = $wrap.data('generate-url');
        var $btn = $(this);
        var $msg = $('#drProfileImageAdminMsg');
        if (!url) {
            return;
        }
        $btn.prop('disabled', true);
        drProfileSetMsg($msg, 'Generating preview with Gemini… this may take up to a minute.', false);
        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': drProfileCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            },
            success: function (res) {
                if (res && res.success && res.preview_url) {
                    var bust = res.preview_url + (res.preview_url.indexOf('?') >= 0 ? '&' : '?') + 't=' + Date.now();
                    $('#drProfilePendingImg').attr('src', bust);
                    $('#drProfilePendingWrap').removeClass('d-none');
                    $('#drProfilePendingEmpty').addClass('d-none');
                    $('#drProfileApproveBtn').removeClass('d-none');
                    $('#drProfileImageStatusBadge').removeClass('badge-success badge-danger').addClass('badge-warning').text('Pending admin review');
                    toastr.success(res.message || 'Preview ready');
                    drProfileSetMsg($msg, 'Preview generated. Review the image, then click Approve.', false);
                } else {
                    toastr.error((res && res.message) ? res.message : 'Generation failed');
                    drProfileSetMsg($msg, (res && res.message) ? res.message : 'Generation failed', true);
                }
            },
            error: function (xhr) {
                var m = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed';
                toastr.error(m);
                drProfileSetMsg($msg, m, true);
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '#drProfileApproveBtn', function () {
        var $wrap = $(this).closest('.dr-profile-image-admin');
        var url = $wrap.data('approve-url');
        var id = parseInt($wrap.data('dr-id'), 10) || 0;
        var $btn = $(this);
        var $msg = $('#drProfileImageAdminMsg');
        if (!url || !confirm('Approve this profile photo for the public CareWeb consultation page?')) {
            return;
        }
        $btn.prop('disabled', true);
        drProfileSetMsg($msg, 'Approving…', false);
        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': drProfileCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            },
            success: function (res) {
                if (res && res.success) {
                    toastr.success(res.message || 'Approved');
                    if (typeof drRegReloadCard === 'function' && id) {
                        drRegReloadCard(id);
                    } else if ($('#doctorViewModalBody').length && id) {
                        $.get('{{ url('subadmin/doctor-requests') }}/' + id + '/view-modal', function (html) {
                            $('#doctorViewModalBody').html(html);
                        });
                    } else {
                        window.location.reload();
                    }
                } else {
                    toastr.error((res && res.message) ? res.message : 'Failed');
                    drProfileSetMsg($msg, (res && res.message) ? res.message : 'Failed', true);
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                var m = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed';
                toastr.error(m);
                drProfileSetMsg($msg, m, true);
                $btn.prop('disabled', false);
            }
        });
    });
})();
</script>
