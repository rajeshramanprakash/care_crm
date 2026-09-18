<script>
(function() {
    if (window.__drWebsiteReviewsShowInit) return;
    window.__drWebsiteReviewsShowInit = true;

    function bsModalShow(id) {
        var el = document.getElementById(id);
        if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    }

    window.openDoctorWebsiteReviewsModal = function(id) {
        var $body = jQuery('#doctorWebsiteReviewsModalBody');
        $body.html('<div class="text-center p-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2 mb-0">Loading…</p></div>');
        bsModalShow('doctorWebsiteReviewsModal');
        jQuery.ajax({
            url: '{{ url('subadmin/doctor-requests') }}/' + id + '/website-reviews/panel',
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function(html) {
            $body.html(html);
        }).fail(function() {
            $body.html('<div class="p-3"><div class="alert alert-danger mb-0">Could not load reviews panel.</div></div>');
        });
    };

    jQuery(document).on('click', '.dr-wr-delete', function(e) {
        e.preventDefault();
        var url = jQuery(this).data('url');
        if (!url || !confirm('Delete this review from the website?')) return;
        var $btn = jQuery(this);
        $btn.prop('disabled', true);
        jQuery.ajax({
            url: url,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(res) {
                if (res && res.success) {
                    if (typeof toastr !== 'undefined') toastr.success(res.message || 'Removed');
                    if (res.html) jQuery('#doctorWebsiteReviewsModalBody').html(res.html);
                } else {
                    if (typeof toastr !== 'undefined') toastr.error((res && res.message) ? res.message : 'Failed');
                }
            },
            error: function(xhr) {
                var m = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed';
                if (typeof toastr !== 'undefined') toastr.error(m);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    jQuery(document).on('submit', '#drWebsiteReviewAddForm', function(e) {
        e.preventDefault();
        var $f = jQuery(this);
        var $msg = $f.find('.dr-wr-form-msg');
        $msg.text('Saving…').removeClass('text-danger text-success');
        var fd = new FormData(this);
        var url = $f.data('store-url');
        jQuery.ajax({
            url: url,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res && res.success) {
                    if (typeof toastr !== 'undefined') toastr.success(res.message || 'Saved');
                    if (res.html) jQuery('#doctorWebsiteReviewsModalBody').html(res.html);
                } else {
                    if (typeof toastr !== 'undefined') toastr.error((res && res.message) ? res.message : 'Failed');
                }
            },
            error: function(xhr) {
                var m = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    m = Object.values(xhr.responseJSON.errors).map(function(a) { return a.join(' '); }).join(' ');
                }
                if (typeof toastr !== 'undefined') toastr.error(m);
                $msg.text(m).addClass('text-danger');
            }
        });
    });
})();
</script>
