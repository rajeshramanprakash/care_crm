import re

file_path = 'resources/views/admin/layouts/sidebar.blade.php'
with open(file_path, 'r') as f:
    content = f.read()

content = content.replace("route('admin.payments.index')", "route($rolePrefix . '.payments.index')")

with open(file_path, 'w') as f:
    f.write(content)

print("Updated sidebar.blade.php for Payments.")
