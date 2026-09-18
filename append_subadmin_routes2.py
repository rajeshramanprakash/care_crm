import re

file_path = 'routes/web.php'
with open(file_path, 'r') as f:
    content = f.read()

new_routes = """
    // B2B & Reference Users
    Route::get('b2b-users', [Controllers\\SubAdmin\\SubAdminB2BUserController::class, 'index'])->name('subadmin.b2b_users.index');
    Route::post('b2b-users', [Controllers\\SubAdmin\\SubAdminB2BUserController::class, 'store'])->name('subadmin.b2b_users.store');
    Route::post('b2b-users/{b2bUser}', [Controllers\\SubAdmin\\SubAdminB2BUserController::class, 'update'])->name('subadmin.b2b_users.update');
    Route::delete('b2b-users/{b2bUser}', [Controllers\\SubAdmin\\SubAdminB2BUserController::class, 'destroy'])->name('subadmin.b2b_users.destroy');
    Route::get('b2b-users/options', [Controllers\\SubAdmin\\SubAdminB2BUserController::class, 'apiIndex'])->name('subadmin.b2b_users.options');

    Route::post('b2b-reference-users', [Controllers\\SubAdmin\\SubAdminB2BReferenceUserController::class, 'store'])->name('subadmin.b2b_reference_users.store');
    Route::delete('b2b-reference-users/{b2bReferenceUser}', [Controllers\\SubAdmin\\SubAdminB2BReferenceUserController::class, 'destroy'])->name('subadmin.b2b_reference_users.destroy');

    Route::get('b2b-corporate-partners', [Controllers\\SubAdmin\\SubAdminB2BPartnerAccountController::class, 'indexCorporate'])->name('subadmin.b2b_corporate.index');
    Route::post('b2b-corporate-partners', [Controllers\\SubAdmin\\SubAdminB2BPartnerAccountController::class, 'storeCorporate'])->name('subadmin.b2b_corporate.store');
    Route::post('b2b-corporate-partners/{b2bUser}', [Controllers\\SubAdmin\\SubAdminB2BPartnerAccountController::class, 'updateCorporate'])->name('subadmin.b2b_corporate.update');
    Route::delete('b2b-corporate-partners/{b2bUser}', [Controllers\\SubAdmin\\SubAdminB2BPartnerAccountController::class, 'destroyCorporate'])->name('subadmin.b2b_corporate.destroy');
    Route::get('b2b-individual-partners', [Controllers\\SubAdmin\\SubAdminB2BPartnerAccountController::class, 'indexIndividual'])->name('subadmin.b2b_individual.index');
    Route::post('b2b-individual-partners', [Controllers\\SubAdmin\\SubAdminB2BPartnerAccountController::class, 'storeIndividual'])->name('subadmin.b2b_individual.store');
    Route::post('b2b-individual-partners/{b2bUser}', [Controllers\\SubAdmin\\SubAdminB2BPartnerAccountController::class, 'updateIndividual'])->name('subadmin.b2b_individual.update');
    Route::delete('b2b-individual-partners/{b2bUser}', [Controllers\\SubAdmin\\SubAdminB2BPartnerAccountController::class, 'destroyIndividual'])->name('subadmin.b2b_individual.destroy');

    // Referral Leads
    Route::get('/referral-leads', [Controllers\\SubAdmin\\SubAdminReferralLeadController::class, 'index'])->name('subadmin.referral_leads.index');
    Route::post('/referral-leads/commission', [Controllers\\SubAdmin\\SubAdminReferralLeadController::class, 'updateCommission'])->name('subadmin.referral_leads.commission');

    // Vendor Payments
    Route::get('/vendor-payments', [Controllers\\SubAdmin\\SubAdminVendorPaymentController::class, 'index'])->name('subadmin.vendor_payments.index');
    Route::get('/vendor-payments/history', [Controllers\\SubAdmin\\SubAdminVendorPaymentController::class, 'vendorHistory'])->name('subadmin.vendor_payments.history');
    Route::post('/vendor-payments', [Controllers\\SubAdmin\\SubAdminVendorPaymentController::class, 'store'])->name('subadmin.vendor_payments.store');
    Route::get('/vendor-payments/{id}', [Controllers\\SubAdmin\\SubAdminVendorPaymentController::class, 'show'])->name('subadmin.vendor_payments.show');
    Route::get('/vendor-payments/{id}/edit', [Controllers\\SubAdmin\\SubAdminVendorPaymentController::class, 'edit'])->name('subadmin.vendor_payments.edit');
    Route::put('/vendor-payments/{id}', [Controllers\\SubAdmin\\SubAdminVendorPaymentController::class, 'update'])->name('subadmin.vendor_payments.update');
    Route::delete('/vendor-payments/{id}', [Controllers\\SubAdmin\\SubAdminVendorPaymentController::class, 'destroy'])->name('subadmin.vendor_payments.destroy');
    Route::get('/vendor-payments/vendor/{vendorId}', [Controllers\\SubAdmin\\SubAdminVendorPaymentController::class, 'getVendorPayments'])->name('subadmin.vendor_payments.vendor');
    Route::get('/vendor-payments/vendor/{vendorId}/statement', [Controllers\\SubAdmin\\SubAdminVendorPaymentController::class, 'statement'])->name('subadmin.vendor_payments.statement');
    Route::get('/vendor-payments/stats', [Controllers\\SubAdmin\\SubAdminVendorPaymentController::class, 'getPaymentStats'])->name('subadmin.vendor_payments.stats');
    Route::get('/vendor-payments/{id}/screenshot', [Controllers\\SubAdmin\\SubAdminVendorPaymentController::class, 'downloadScreenshot'])->name('subadmin.vendor_payments.screenshot');
    Route::get('/vendor-payments/{id}/invoice', [Controllers\\SubAdmin\\SubAdminVendorPaymentController::class, 'invoice'])->name('subadmin.vendor_payments.invoice');

    // Freelancer Payments
    Route::get('/freelancer-payments', [Controllers\\SubAdmin\\SubAdminFreelancerPaymentController::class, 'index'])->name('subadmin.freelancer_payments.index');
    Route::post('/freelancer-payments', [Controllers\\SubAdmin\\SubAdminFreelancerPaymentController::class, 'store'])->name('subadmin.freelancer_payments.store');
    Route::get('/freelancer-payments/{id}', [Controllers\\SubAdmin\\SubAdminFreelancerPaymentController::class, 'show'])->name('subadmin.freelancer_payments.show');
    Route::get('/freelancer-payments/{id}/edit', [Controllers\\SubAdmin\\SubAdminFreelancerPaymentController::class, 'edit'])->name('subadmin.freelancer_payments.edit');
    Route::put('/freelancer-payments/{id}', [Controllers\\SubAdmin\\SubAdminFreelancerPaymentController::class, 'update'])->name('subadmin.freelancer_payments.update');
    Route::delete('/freelancer-payments/{id}', [Controllers\\SubAdmin\\SubAdminFreelancerPaymentController::class, 'destroy'])->name('subadmin.freelancer_payments.destroy');
    Route::get('/freelancer-payments/{id}/invoice', [Controllers\\SubAdmin\\SubAdminFreelancerPaymentController::class, 'invoice'])->name('subadmin.freelancer_payments.invoice');
    Route::get('/freelancer-payments/statement/{freelancerId}', [Controllers\\SubAdmin\\SubAdminFreelancerPaymentController::class, 'statement'])->name('subadmin.freelancer_payments.statement');
    Route::get('/freelancer-payments/freelancer/{freelancerId}', [Controllers\\SubAdmin\\SubAdminFreelancerPaymentController::class, 'getFreelancerPayments'])->name('subadmin.freelancer_payments.freelancer');

    // Locations
    Route::get('/locations', [Controllers\\SubAdmin\\SubAdminLocationController::class, 'index'])->name('subadmin.locations.index');
    Route::post('/locations', [Controllers\\SubAdmin\\SubAdminLocationController::class, 'store'])->name('subadmin.locations.store');
    Route::get('/locations/{location}/edit', [Controllers\\SubAdmin\\SubAdminLocationController::class, 'edit'])->name('subadmin.locations.edit');
    Route::put('/locations/{location}', [Controllers\\SubAdmin\\SubAdminLocationController::class, 'update'])->name('subadmin.locations.update');
    Route::delete('/locations/{location}', [Controllers\\SubAdmin\\SubAdminLocationController::class, 'destroy'])->name('subadmin.locations.destroy');
"""

# Insert right before the last }); of the Sub Admin block.
target_line = r"Route::delete\('operation-leads/received-payment/\{id\}', \[App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'destroyReceivedPayment'\]\)->name\('subadmin\.operation_leads\.received_payment\.destroy'\);"

if "subadmin.vendor_payments.index" not in content:
    content = re.sub(target_line, lambda m: m.group(0) + "\n" + new_routes, content)
    with open(file_path, 'w') as f:
        f.write(content)
    print("Added new SubAdmin routes to web.php")
else:
    print("Routes already exist.")

