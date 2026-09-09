<nav class="main-header navbar navbar-expand navbar-dark navbar-light" style="background: var(--wb-renosand); position: sticky; top: 0; z-index: 1030;">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link" data-widget="pushmenu" id="sidebar_collapsible_elem"
                data-collapse="1" onclick="handle_sidebar_collapse(this)"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
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
        
        <!-- Duty & Break Status Toggles -->
        <li class="nav-item d-flex align-items-center ml-2">
            <div class="custom-control custom-switch mr-3">
                <input type="checkbox" class="custom-control-input" id="dutyStatusSwitch" disabled>
                <label class="custom-control-label" for="dutyStatusSwitch" style="cursor: pointer;">Duty Status</label>
            </div>
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="breakStatusSwitch" disabled>
                <label class="custom-control-label" for="breakStatusSwitch" style="cursor: pointer;">Break Status</label>
            </div>
        </li>
    </ul>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dutySwitch = document.getElementById('dutyStatusSwitch');
    const breakSwitch = document.getElementById('breakStatusSwitch');
    const token = localStorage.getItem('token'); // Assuming token is stored here, or we use session auth
    
    // Function to fetch status
    function fetchStatus() {
        fetch('/api/user/status', {
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json',
                // If using session auth (web middleware), we might not need bearer token but X-CSRF-TOKEN
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Update switches without triggering change events
                const dutyState = !!data.is_active;
                const breakState = !!data.is_break;
                
                if (dutySwitch.checked !== dutyState) {
                    dutySwitch.checked = dutyState;
                }
                
                if (breakSwitch.checked !== breakState) {
                    breakSwitch.checked = breakState;
                }
                
                // Enable switches after first fetch
                dutySwitch.disabled = false;
                breakSwitch.disabled = !dutyState; // Can only take break if on duty
            }
        })
        .catch(error => console.error('Error fetching status:', error));
    }

    // Initial fetch
    fetchStatus();

    // Poll every 10 seconds
    setInterval(fetchStatus, 10000);

    // Handle Duty Toggle
    dutySwitch.addEventListener('change', function(e) {
        // Optimistic UI update or wait for response? Let's wait for response to be safe or revert on failure
        const newState = this.checked;
        this.disabled = true; // Disable while processing
        
        fetch('/api/user/status/toggle-duty', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ reason: newState ? 'Started duty' : 'Off duty' })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Update state from response
                dutySwitch.checked = !!data.is_active;
                breakSwitch.checked = !!data.is_break;
                
                // Update break switch enabled state
                breakSwitch.disabled = !data.is_active;
                
                toastr.success(data.message);
            } else {
                // Revert on failure
                dutySwitch.checked = !newState;
                toastr.error(data.message || 'Failed to update duty status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            dutySwitch.checked = !newState;
            toastr.error('System error occurred');
        })
        .finally(() => {
            dutySwitch.disabled = false;
        });
    });

    // Handle Break Toggle
    breakSwitch.addEventListener('change', function(e) {
        const newState = this.checked;
        this.disabled = true;
        
        fetch('/api/user/status/toggle-break', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ reason: newState ? 'Break' : 'Break ended' })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                breakSwitch.checked = !!data.is_break;
                toastr.success(data.message);
            } else {
                breakSwitch.checked = !newState;
                toastr.error(data.message || 'Failed to update break status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            breakSwitch.checked = !newState;
            toastr.error('System error occurred');
        })
        .finally(() => {
            breakSwitch.disabled = false;
        });
    });
});
</script>
