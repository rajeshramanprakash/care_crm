import os
import glob

# Paths to update
files_to_update = [
    'app/Http/Controllers/SubAdmin/SubAdminController.php',
    'app/Http/Controllers/SubAdmin/SubAdminLeadController.php',
    'app/Http/Controllers/SubAdmin/SubAdminOperationLeadController.php',
    'resources/views/subadmin/dashboard.blade.php',
    'resources/views/subadmin/leads/index.blade.php',
    'resources/views/subadmin/leads/show.blade.php',
    'resources/views/subadmin/operation_leads/index.blade.php',
    'resources/views/subadmin/operation_leads/show.blade.php',
    'resources/views/subadmin/operation_leads/payment_invoice_show.blade.php',
]

for file_path in files_to_update:
    if os.path.exists(file_path):
        with open(file_path, 'r') as f:
            content = f.read()
            
        # Replace views
        content = content.replace("view('admin.dashboard", "view('subadmin.dashboard")
        content = content.replace("view('admin.leads", "view('subadmin.leads")
        content = content.replace("view('admin.operation_leads", "view('subadmin.operation_leads")
        
        # Replace routes
        content = content.replace("route('admin.leads", "route('subadmin.leads")
        content = content.replace("route('admin.operation_leads", "route('subadmin.operation_leads")
        content = content.replace("route('admin.dashboard", "route('subadmin.dashboard")
        
        with open(file_path, 'w') as f:
            f.write(content)
        print(f"Updated {file_path}")

