<nav class="main-header navbar navbar-expand navbar-dark navbar-light" style="background: var(--wb-renosand); position: sticky; top: 0; z-index: 1030;">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link" data-widget="pushmenu" id="sidebar_collapsible_elem"
                data-collapse="1" onclick="handle_sidebar_collapse(this)"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item d-flex align-items-center me-2 gap-3">
            <!-- Duty Status Toggle -->
            <span id="navbar-duty-toggle" class="duty-toggle-icon" data-user-id="{{ Auth::id() }}" data-status="{{ Auth::user()->is_active ? 1 : 0 }}" style="cursor:pointer; font-size:2rem; color:{{ Auth::user()->is_active ? '#198754' : '#adb5bd' }};">
                <i class="fas fa-toggle-{{ Auth::user()->is_active ? 'on' : 'off' }}"></i>
            </span>
            <span id="navbar-duty-label" class="ms-1 {{ Auth::user()->is_active ? 'toggle-label-on' : 'toggle-label-off' }}">{{ Auth::user()->is_active ? 'On Duty' : 'Off Duty' }}</span>
            &nbsp;
            &nbsp;
            &nbsp;
            &nbsp;
            <!-- Break Status Toggle -->
            <span id="navbar-break-toggle" class="break-toggle-icon" data-user-id="{{ Auth::id() }}" data-status="{{ Auth::user()->is_break ? 1 : 0 }}" style="cursor:pointer; font-size:2rem; color:{{ !Auth::user()->is_break ? '#198754' : '#dc3545' }};">
                <i class="fas fa-toggle-{{ !Auth::user()->is_break ? 'on' : 'off' }}"></i>
            </span>
            <span id="navbar-break-label" class="ms-1 {{ !Auth::user()->is_break ? 'toggle-label-on' : 'toggle-label-off' }}">{{ !Auth::user()->is_break ? 'Active' : 'Break' }}</span>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" title="logout" onclick="return confirm('Are you sure want to logout?')"
                href="{{ route('logout') }}">
                <i class="fas fa-power-off"></i>
            </a>
        </li>
        @yield('navbar-right-links')
    </ul>
</nav>

<!-- Break Reason Modal -->
<div class="modal fade" id="navbarBreakReasonModal" tabindex="-1" aria-labelledby="navbarBreakReasonModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="navbarBreakReasonModalLabel">Enter Break Reason</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="navbarBreakReasonForm">
          <div class="mb-3">
            <label for="navbar-break-reason" class="form-label">Reason</label>
            <input type="text" class="form-control" id="navbar-break-reason" name="break_reason" required>
          </div>
          <button type="submit" class="btn btn-primary">Submit</button>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
    .toggle-label-on {
        color: #198754;
        font-weight: 600;
    }
    .toggle-label-off {
        color: #dc3545;
        font-weight: 600;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const authId = {{ Auth::id() }};
        // Duty toggle logic
        document.getElementById('navbar-duty-toggle').addEventListener('click', function() {
            var $icon = $(this);
            var isActive = $icon.data('status') == 1 ? 0 : 1; // toggle
            if (isActive) {
                // Set to On Duty
                fetch(`/duty_active/${authId}/1`).then(r => r.json()).then(function(response) {
                    toastr.success('Set to On Duty');
                    $icon.data('status', 1);
                    $icon.css('color', '#198754');
                    $icon.find('i').removeClass('fa-toggle-off').addClass('fa-toggle-on');
                    $('#navbar-duty-label').removeClass('toggle-label-off').addClass('toggle-label-on').text('On Duty');
                });
            } else {
                // Set to Off Duty (show modal for reason)
                $('#navbarBreakReasonModalLabel').text('Enter Duty Off Reason');
                $('#navbarBreakReasonForm').data('toggle-type', 'duty');
                var modal = new bootstrap.Modal(document.getElementById('navbarBreakReasonModal'));
                modal.show();
            }
        });
        // Break toggle logic
        document.getElementById('navbar-break-toggle').addEventListener('click', function() {
            var $icon = $(this);
            var isBreak = $icon.data('status') == 0 ? 1 : 0; // toggle
            if (isBreak === 0) {
                // Set to Active
                fetch(`/break_active/${authId}/0`).then(r => r.json()).then(function(response) {
                    toastr.success('Set to Active');
                    $icon.data('status', 0);
                    $icon.css('color', '#198754');
                    $icon.find('i').removeClass('fa-toggle-off').addClass('fa-toggle-on');
                    $('#navbar-break-label').removeClass('toggle-label-off').addClass('toggle-label-on').text('Active');
                });
            } else {
                // Set to Break (show modal for reason)
                $('#navbarBreakReasonModalLabel').text('Enter Break Reason');
                $('#navbarBreakReasonForm').data('toggle-type', 'break');
                var modal = new bootstrap.Modal(document.getElementById('navbarBreakReasonModal'));
                modal.show();
            }
        });
        // Modal submit logic for both toggles
        document.getElementById('navbarBreakReasonForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const breakReason = document.getElementById('navbar-break-reason').value;
            var toggleType = $('#navbarBreakReasonForm').data('toggle-type');
            var url = '';
            if (toggleType === 'duty') {
                url = `/duty_active/${authId}/0/${encodeURIComponent(breakReason)}`;
            } else {
                url = `/break_active/${authId}/1/${encodeURIComponent(breakReason)}`;
            }
            fetch(url).then(r => r.json()).then(function(response) {
                toastr.success('Status updated');
                var modal = bootstrap.Modal.getInstance(document.getElementById('navbarBreakReasonModal'));
                modal.hide();
                if (toggleType === 'duty') {
                    var $icon = $('#navbar-duty-toggle');
                    $icon.data('status', 0);
                    $icon.css('color', '#adb5bd');
                    $icon.find('i').removeClass('fa-toggle-on').addClass('fa-toggle-off');
                    $('#navbar-duty-label').removeClass('toggle-label-on').addClass('toggle-label-off').text('Off Duty');
                } else {
                    var $icon = $('#navbar-break-toggle');
                    $icon.data('status', 1);
                    $icon.css('color', '#dc3545');
                    $icon.find('i').removeClass('fa-toggle-on').addClass('fa-toggle-off');
                    $('#navbar-break-label').removeClass('toggle-label-on').addClass('toggle-label-off').text('Break');
                }
            });
        });
    });
</script>
