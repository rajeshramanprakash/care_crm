import os

folders_to_update = [
    'resources/views/subadmin/vendor_payments',
    'resources/views/subadmin/freelancer_payments',
    'resources/views/subadmin/referral_leads',
    'resources/views/subadmin/b2b_users',
    'resources/views/subadmin/b2b_reference_users',
    'resources/views/subadmin/b2b_partner_accounts',
    'resources/views/subadmin/locations'
]

for folder in folders_to_update:
    for root, dirs, files in os.walk(folder):
        for file in files:
            if file.endswith('.blade.php'):
                file_path = os.path.join(root, file)
                with open(file_path, 'r') as f:
                    content = f.read()
                
                original_content = content
                
                # Replace JS URL prefixes
                content = content.replace("'/admin/", "'/subadmin/")
                content = content.replace("`/admin/", "`/subadmin/")
                content = content.replace('"/admin/', '"/subadmin/')
                
                # Additional generic replacements for href attribute
                content = content.replace('href="/admin/', 'href="/subadmin/')
                content = content.replace("href='/admin/", "href='/subadmin/")
                
                # Replace route helpers
                content = content.replace("route('admin.", "route('subadmin.")
                content = content.replace('route("admin.', 'route("subadmin.')
                
                if content != original_content:
                    with open(file_path, 'w') as f:
                        f.write(content)
                    print(f"Updated JS URLs and routes in {file_path}")

print("JS URL update complete.")
