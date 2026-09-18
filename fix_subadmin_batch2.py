import os
import glob

# Paths to update
files_to_update = [
    'app/Http/Controllers/SubAdmin/SubAdminVendorPaymentController.php',
    'app/Http/Controllers/SubAdmin/SubAdminFreelancerPaymentController.php',
    'app/Http/Controllers/SubAdmin/SubAdminReferralLeadController.php',
    'app/Http/Controllers/SubAdmin/SubAdminB2BUserController.php',
    'app/Http/Controllers/SubAdmin/SubAdminB2BReferenceUserController.php',
    'app/Http/Controllers/SubAdmin/SubAdminB2BPartnerAccountController.php',
    'app/Http/Controllers/SubAdmin/SubAdminLocationController.php'
]

for file_path in files_to_update:
    if os.path.exists(file_path):
        with open(file_path, 'r') as f:
            content = f.read()
            
        original = content
        # Replace namespace
        content = content.replace("namespace App\\Http\\Controllers\\Admin;", "namespace App\\Http\\Controllers\\SubAdmin;")
        
        # Replace class names
        content = content.replace("class VendorPaymentController extends Controller", "class SubAdminVendorPaymentController extends Controller")
        content = content.replace("class FreelancerPaymentController extends Controller", "class SubAdminFreelancerPaymentController extends Controller")
        content = content.replace("class ReferralLeadController extends Controller", "class SubAdminReferralLeadController extends Controller")
        content = content.replace("class B2BUserController extends Controller", "class SubAdminB2BUserController extends Controller")
        content = content.replace("class B2BReferenceUserController extends Controller", "class SubAdminB2BReferenceUserController extends Controller")
        content = content.replace("class B2BPartnerAccountController extends Controller", "class SubAdminB2BPartnerAccountController extends Controller")
        content = content.replace("class LocationController extends Controller", "class SubAdminLocationController extends Controller")
        
        # Replace views
        content = content.replace("view('admin.", "view('subadmin.")
        
        if content != original:
            with open(file_path, 'w') as f:
                f.write(content)
            print(f"Updated {file_path}")

