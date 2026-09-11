<script>
(function ($) {
  if (!$) return;

  function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || '';
  }

  $(document).on('click', '.dr-leegality-send-btn', function () {
    var $btn = $(this);
    var url = $btn.data('url');
    if (!url) return;
    if (!window.confirm('Send agreement for e-signature to the doctor email via Leegality?')) return;

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Sending…');
    $.ajax({
      url: url,
      type: 'POST',
      data: { _token: csrfToken() },
      success: function (res) {
        if (res.success) {
          if (window.toastr) toastr.success(res.message || 'Sent');
          if (res.html) $('#drLeegalityPanelWrap').html(res.html);
          if ($.fn.dataTable && $.fn.dataTable.isDataTable('#doctor-requests-table')) {
            $('#doctor-requests-table').DataTable().ajax.reload(null, false);
          }
        } else {
          if (window.toastr) toastr.error(res.message || 'Failed');
          $btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Send for Signature');
        }
      },
      error: function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to send for signature.';
        if (window.toastr) toastr.error(msg); else alert(msg);
        $btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Send for Signature');
      }
    });
  });

  $(document).on('click', '.dr-leegality-add-sig-btn', function () {
    var $btn = $(this);
    var url = $btn.data('url');
    if (!url) return;
    if (!window.confirm('Add your official signature to this document and email final signed PDF to the doctor?')) return;

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Signing & Emailing…');
    $.ajax({
      url: url,
      type: 'POST',
      timeout: 120000,
      data: { _token: csrfToken() },
      success: function (res) {
        if (res.success) {
          if (window.toastr) toastr.success(res.message || 'Signature added & emailed successfully!');
          if (res.html) $('#drLeegalityPanelWrap').html(res.html);
          if ($.fn.dataTable && $.fn.dataTable.isDataTable('#doctor-requests-table')) {
            $('#doctor-requests-table').DataTable().ajax.reload(null, false);
          }
        } else {
          if (window.toastr) toastr.error(res.message || 'Failed to add signature');
          $btn.prop('disabled', false).html('<i class="fas fa-file-signature mr-1"></i> Add My Signature');
        }
      },
      error: function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to add signature.';
        if (window.toastr) toastr.error(msg); else alert(msg);
        $btn.prop('disabled', false).html('<i class="fas fa-file-signature mr-1"></i> Add My Signature');
      }
    });
  });

  $(document).on('click', '.dr-leegality-refresh-btn', function () {
    var $btn = $(this);
    var url = $btn.data('url');
    if (!url) return;
    $btn.prop('disabled', true);
    $.ajax({
      url: url,
      type: 'POST',
      data: { _token: csrfToken() },
      success: function (res) {
        if (res.success) {
          if (window.toastr) toastr.success(res.message || 'Refreshed');
          if (res.html) $('#drLeegalityPanelWrap').html(res.html);
        } else {
          if (window.toastr) toastr.error(res.message || 'Failed');
          $btn.prop('disabled', false);
        }
      },
      error: function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Refresh failed.';
        if (window.toastr) toastr.error(msg); else alert(msg);
        $btn.prop('disabled', false);
      }
    });
  });
})(window.jQuery);
</script>
