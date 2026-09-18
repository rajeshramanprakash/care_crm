import os

file_path = 'app/Http/Controllers/AuthController.php'
with open(file_path, 'r') as f:
    content = f.read()

content = content.replace("'Sub Admin' => 'admin.dashboard'", "'Sub Admin' => 'subadmin.dashboard'")

with open(file_path, 'w') as f:
    f.write(content)
print("Updated AuthController")
