import re

with open('routes/web.php', 'r') as f:
    content = f.read()

# Define the new route group
new_group = """
// Routes for Sub Admin Role
Route::prefix('/subadmin')->middleware(['auth', 'role:Sub Admin'])->group(function () {
    Route::get('/dashboard', [Controllers\\SubAdmin\\SubAdminController::class, 'dashboard'])->name('subadmin.dashboard');
    
    // Sub Admin Leads Routes
    Route::get('/leads', [App\\Http\\Controllers\\SubAdmin\\SubAdminLeadController::class, 'index'])->name('subadmin.leads.index');
    Route::get('/leads/getLeads', [App\\Http\\Controllers\\SubAdmin\\SubAdminLeadController::class, 'getLeads'])->name('subadmin.leads.getLeads');
    Route::get('/leads/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminLeadController::class, 'show'])->name('subadmin.leads.show');
    Route::delete('/leads/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminLeadController::class, 'destroy'])->name('subadmin.leads.destroy');
    Route::post('/leads', [App\\Http\\Controllers\\SubAdmin\\SubAdminLeadController::class, 'store'])->name('subadmin.leads.store');
    Route::get('/leads/{id}/edit', [App\\Http\\Controllers\\SubAdmin\\SubAdminLeadController::class, 'edit'])->name('subadmin.leads.edit');
    Route::put('/leads/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminLeadController::class, 'update'])->name('subadmin.leads.update');
    Route::put('/leads/{lead}/status-remarks/{remark}', [App\\Http\\Controllers\\SubAdmin\\SubAdminLeadController::class, 'updateStatusRemark'])->name('subadmin.leads.status-remarks.update');

    // Sub Admin Operation Leads Routes
    Route::get('/operation-leads', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'index'])->name('subadmin.operation_leads.index');
    Route::post('/operation-leads', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'store'])->name('subadmin.operation_leads.store');
    Route::get('/operation-leads/getLeads', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'getLeads'])->name('subadmin.operation_leads.getLeads');
    Route::get('operation-leads/filtered-vendors', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'getFilteredVendorsForDeployment'])->name('subadmin.operation_leads.filtered_vendors');
    Route::get('operation-leads/vendor-details', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'getVendorDetails'])->name('subadmin.operation_leads.vendor_details');
    Route::post('operation-leads/update-freelancer-status', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'updateFreelancerStatus'])->name('subadmin.operation_leads.update_freelancer_status');
    Route::get('operation-leads/updated-vendor-count', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'getUpdatedVendorCount'])->name('subadmin.operation_leads.updated_vendor_count');
    Route::get('/operation-leads/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'show'])->name('subadmin.operation_leads.show');
    Route::get('/operation-leads/{id}/edit', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'edit'])->name('subadmin.operation_leads.edit');
    Route::put('/operation-leads/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'update'])->name('subadmin.operation_leads.update');
    Route::delete('/operation-leads/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'destroy'])->name('subadmin.operation_leads.destroy');
    Route::post('operation-leads/{lead}/payment', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'storePaymentDetail'])->name('subadmin.operation_leads.payment.store');
    Route::get('operation-leads/payment/{id}/edit', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'editPaymentDetail'])->name('subadmin.operation_leads.payment.edit');
    Route::put('operation-leads/payment/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'updatePaymentDetail'])->name('subadmin.operation_leads.payment.update');
    Route::delete('operation-leads/payment/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'destroyPaymentDetail'])->name('subadmin.operation_leads.payment.destroy');
    Route::post('operation-leads/{lead}/deployment', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'storeDeploymentDetail'])->name('subadmin.operation_leads.deployment.store');
    Route::get('operation-leads/deployment/{id}/edit', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'editDeploymentDetail'])->name('subadmin.operation_leads.deployment.edit');
    Route::put('operation-leads/deployment/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'updateDeploymentDetail'])->name('subadmin.operation_leads.deployment.update');
    Route::delete('operation-leads/deployment/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'destroyDeploymentDetail'])->name('subadmin.operation_leads.deployment.destroy');
    Route::post('operation-leads/deployment/{id}/toggle-verify-payment', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'toggleVerifyPayment'])->name('subadmin.operation_leads.deployment.toggle-verify-payment');
    Route::post('operation-leads/deployment/{id}/dates', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'getDeploymentDates'])->name('subadmin.operation_leads.deployment.dates');
    Route::post('operation-leads/deployment/{id}/toggle-absent', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'toggleDeploymentAbsentDate'])->name('subadmin.operation_leads.deployment.toggle-absent');
    Route::post('operation-leads/deployment/{id}/save-absent', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'saveDeploymentAbsentDates'])->name('subadmin.operation_leads.deployment.save-absent');
    Route::get('operation-leads/payment-invoice/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'showPaymentInvoice'])->name('subadmin.operation_leads.payment_invoice.show');
    Route::post('operation-leads/invoice/{invoiceId}/received-payment', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'storeReceivedPayment'])->name('subadmin.operation_leads.received_payment.store');
    Route::get('operation-leads/received-payment/{id}/edit', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'editReceivedPayment'])->name('subadmin.operation_leads.received_payment.edit');
    Route::put('operation-leads/received-payment/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'updateReceivedPayment'])->name('subadmin.operation_leads.received_payment.update');
    Route::delete('operation-leads/received-payment/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'destroyReceivedPayment'])->name('subadmin.operation_leads.received_payment.destroy');
});
"""

# check if already added
if "Route::prefix('/subadmin')" not in content:
    content += "\n" + new_group
    with open('routes/web.php', 'w') as f:
        f.write(content)
    print("Added subadmin routes to web.php")
else:
    print("Subadmin routes already present.")
