import re

file_path = 'resources/views/admin/layouts/sidebar.blade.php'
with open(file_path, 'r') as f:
    content = f.read()

# Replace links for B2B Users, Corporate Partners, Individual Partners, Reference Users
content = content.replace("route('admin.b2b_users.index')", "route($rolePrefix . '.b2b_users.index')")
content = content.replace("route('admin.b2b_corporate.index')", "route($rolePrefix . '.b2b_corporate.index')")
content = content.replace("route('admin.b2b_individual.index')", "route($rolePrefix . '.b2b_individual.index')")
content = content.replace("route('admin.b2b_reference_users.index')", "route($rolePrefix . '.b2b_reference_users.index')")

# For Referral Leads
content = content.replace("route('admin.referral_leads.index')", "route($rolePrefix . '.referral_leads.index')")

# For Vendor Payments & Freelancer Payments
content = content.replace("route('admin.vendor_payments.index')", "route($rolePrefix . '.vendor_payments.index')")
content = content.replace("route('admin.vendor_payments.history')", "route($rolePrefix . '.vendor_payments.history')")
content = content.replace("route('admin.freelancer_payments.index')", "route($rolePrefix . '.freelancer_payments.index')")

# For Locations
content = content.replace("route('admin.locations.index')", "route($rolePrefix . '.locations.index')")

with open(file_path, 'w') as f:
    f.write(content)

print("Updated sidebar.blade.php with role prefixes.")
