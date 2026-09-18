import re

file_path = 'resources/views/admin/layouts/sidebar.blade.php'
with open(file_path, 'r') as f:
    content = f.read()

# Add php block at the top if not present
if "$rolePrefix" not in content:
    php_block = """
@php
    $rolePrefix = auth()->check() && session('role_name') === 'Sub Admin' ? 'subadmin' : 'admin';
@endphp
"""
    content = php_block + content

# Replace route('admin.dashboard')
content = content.replace("route('admin.dashboard')", "route($rolePrefix . '.dashboard')")
# Replace route('admin.leads.index')
content = content.replace("route('admin.leads.index')", "route($rolePrefix . '.leads.index')")
# Replace route('admin.operation_leads.index')
content = content.replace("route('admin.operation_leads.index')", "route($rolePrefix . '.operation_leads.index')")

# Fix active class logic
content = content.replace("str_starts_with($route_name, 'admin.leads.')", "str_starts_with($route_name, $rolePrefix . '.leads.')")

with open(file_path, 'w') as f:
    f.write(content)
print("Sidebar updated")
