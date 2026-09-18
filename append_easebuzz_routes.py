import re

file_path = 'routes/web.php'
with open(file_path, 'r') as f:
    content = f.read()

new_routes = """
    Route::get('/payments', [App\\Http\\Controllers\\EasebuzzPaymentLinkController::class, 'index'])->name('subadmin.payments.index');
    Route::get('/payments/create', [App\\Http\\Controllers\\EasebuzzPaymentLinkController::class, 'create'])->name('subadmin.payments.create');
    Route::post('/payments', [App\\Http\\Controllers\\EasebuzzPaymentLinkController::class, 'store'])->name('subadmin.payments.store');
    Route::get('/payments/{payment}', [App\\Http\\Controllers\\EasebuzzPaymentLinkController::class, 'show'])->name('subadmin.payments.show');
    Route::post('/payments/{payment}/verify', [App\\Http\\Controllers\\EasebuzzPaymentLinkController::class, 'verify'])->name('subadmin.payments.verify');

"""

target_line = r"    // Sub Admin Leads Routes"

if "subadmin.payments.index" not in content:
    content = re.sub(target_line, lambda m: new_routes + m.group(0), content)
    with open(file_path, 'w') as f:
        f.write(content)
    print("Added Easebuzz routes for subadmin.")
else:
    print("Routes already exist.")

