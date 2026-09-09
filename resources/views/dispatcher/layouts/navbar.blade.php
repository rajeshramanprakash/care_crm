<nav class="main-header navbar navbar-expand navbar-dark navbar-light" style="background: var(--wb-renosand)">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link" data-widget="pushmenu" id="sidebar_collapsible_elem"
                data-collapse="1" onclick="handle_sidebar_collapse(this)"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        @php
            $user = auth()->user();
            $roleIds = explode(',', $user->role_id);
            $roles = \App\Models\Role::whereIn('id', $roleIds)->get();
        @endphp
        @if(count($roleIds) > 1)
            <li class="nav-item">
                <a class="nav-link" title="Change Role" href="#" data-bs-toggle="modal" data-bs-target="#roleSelectModal">
                    <i class="fas fa-user-shield"></i> Change Role
                </a>
            </li>
        @endif
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

@if(count($roleIds) > 1)
<!-- Role Select Modal -->
<div class="modal fade" id="roleSelectModal" tabindex="-1" aria-labelledby="roleSelectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('login.role.select') }}" id="role-form-modal">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="roleSelectModalLabel">Select Your Role</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="role" id="selected-role-modal">
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    {{-- Debug: --}}
                    @php
                        // echo '<pre>roleIds: '; print_r($roleIds); echo '</pre>';
                        // echo '<pre>roles: '; print_r($roles->toArray()); echo '</pre>';
                    @endphp
                    <div class="role-cards d-flex flex-wrap gap-2">
                        @foreach ($roles as $role)
                            <div class="role-card border p-2 rounded flex-fill text-center mb-2"
                                 data-role="{{ $role->id }}"
                                 style="cursor:pointer;">
                                {{ $role->name }}
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Proceed</button>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    .role-card.selected {
        border: 2px solid #28a745;
        background: #eafaea;
        color: #28a745;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleCards = document.querySelectorAll('.role-card');
        const hiddenInput = document.getElementById('selected-role-modal');
        roleCards.forEach(card => {
            card.addEventListener('click', () => {
                roleCards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                hiddenInput.value = card.getAttribute('data-role');
            });
        });
        document.getElementById('role-form-modal').addEventListener('submit', function (e) {
            if (!hiddenInput.value) {
                alert('Please select a role before proceeding.');
                e.preventDefault();
            }
        });
    });
</script>
@endif
