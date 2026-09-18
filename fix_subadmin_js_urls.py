import os
import glob

# Search in subadmin views
for root, dirs, files in os.walk('resources/views/subadmin'):
    for file in files:
        if file.endswith('.blade.php'):
            file_path = os.path.join(root, file)
            with open(file_path, 'r') as f:
                content = f.read()
            
            original_content = content
            
            # Replace JS URL prefixes
            content = content.replace("'/admin/dashboard", "'/subadmin/dashboard")
            content = content.replace("`/admin/dashboard", "`/subadmin/dashboard")
            content = content.replace('"/admin/dashboard', '"/subadmin/dashboard')
            
            content = content.replace("'/admin/leads", "'/subadmin/leads")
            content = content.replace("`/admin/leads", "`/subadmin/leads")
            content = content.replace('"/admin/leads', '"/subadmin/leads')
            
            content = content.replace("'/admin/operation-leads", "'/subadmin/operation-leads")
            content = content.replace("`/admin/operation-leads", "`/subadmin/operation-leads")
            content = content.replace('"/admin/operation-leads', '"/subadmin/operation-leads')
            
            # Additional generic replacements for href attribute
            content = content.replace('href="/admin/operation-leads', 'href="/subadmin/operation-leads')
            content = content.replace('href="/admin/leads', 'href="/subadmin/leads')
            content = content.replace('href="/admin/dashboard', 'href="/subadmin/dashboard')
            
            if content != original_content:
                with open(file_path, 'w') as f:
                    f.write(content)
                print(f"Updated JS URLs in {file_path}")

print("JS URL update complete.")
