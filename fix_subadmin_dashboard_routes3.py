import re

file_path = 'routes/web.php'
with open(file_path, 'r') as f:
    content = f.read()

missing_routes = """
    Route::get('/dashboard/sales-leads-stats', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getSalesLeadsStats'])->name('subadmin.dashboard.sales-leads-stats');
    Route::get('/dashboard/operation-leads-stats', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getOperationLeadsStats'])->name('subadmin.dashboard.operation-leads-stats');
    Route::get('/dashboard/users-stats', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getUsersStats'])->name('subadmin.dashboard.users-stats');
    Route::get('/dashboard/user-details/{userId}', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getUserDetails'])->name('subadmin.dashboard.user-details');
    Route::get('/dashboard/sales-executives', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getSalesExecutives'])->name('subadmin.dashboard.sales-executives');
    Route::get('/dashboard/operation-executives', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getOperationExecutives'])->name('subadmin.dashboard.operation-executives');
    Route::get('/dashboard/users', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getUsers'])->name('subadmin.dashboard.users');

    // Task Management Routes
    Route::get('/dashboard/tasks', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getTasks'])->name('subadmin.dashboard.tasks');
    Route::post('/dashboard/tasks', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'storeTask'])->name('subadmin.dashboard.tasks.store');
    Route::get('/dashboard/tasks/{id}/edit', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'editTask'])->name('subadmin.dashboard.tasks.edit');
    Route::put('/dashboard/tasks/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'updateTask'])->name('subadmin.dashboard.tasks.update');
    Route::delete('/dashboard/tasks/{id}', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'destroyTask'])->name('subadmin.dashboard.tasks.destroy');
    Route::get('/dashboard/task-history', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getTaskHistory'])->name('subadmin.dashboard.task-history');
    Route::get('/dashboard/calendar-data', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getCalendarData'])->name('subadmin.dashboard.calendar-data');
    Route::get('/dashboard/calendar-leads', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getCalendarLeads'])->name('subadmin.dashboard.calendar-leads');
    Route::get('/dashboard/revenue-stats', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getRevenueStats'])->name('subadmin.dashboard.revenue-stats');
    Route::get('/dashboard/pending-deployments', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getPendingDeployments'])->name('subadmin.dashboard.pending-deployments');
    Route::get('/dashboard/outstanding-amount-stats', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getOutstandingAmountStats'])->name('subadmin.dashboard.outstanding-amount-stats');
    Route::get('/dashboard/profile-pending-stats', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getProfilePendingStats'])->name('subadmin.dashboard.profile-pending-stats');
    Route::get('/dashboard/vendor-payment-stats', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getVendorPaymentStats'])->name('subadmin.dashboard.vendor-payment-stats');
    Route::get('/dashboard/unverified-deployment-payments-stats', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getUnverifiedDeploymentPaymentsStats'])->name('subadmin.dashboard.unverified-deployment-payments-stats');
    Route::get('/dashboard/pending-callbacks', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getPendingCallbacks'])->name('subadmin.dashboard.pending-callbacks');
    
    // Recent Calls Routes
    Route::get('/dashboard/recent-calls/sales', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getRecentCallsSales'])->name('subadmin.dashboard.recent-calls.sales');
    Route::get('/dashboard/recent-calls/operation', [App\\Http\\Controllers\\SubAdmin\\SubAdminController::class, 'getRecentCallsOperation'])->name('subadmin.dashboard.recent-calls.operation');
"""

target_line = r"Route::delete\('operation-leads/received-payment/\{id\}', \[App\\Http\\Controllers\\SubAdmin\\SubAdminOperationLeadController::class, 'destroyReceivedPayment'\]\)->name\('subadmin\.operation_leads\.received_payment\.destroy'\);"
if "getUnverifiedDeploymentPaymentsStats" not in content:
    content = re.sub(target_line, lambda m: m.group(0) + "\n" + missing_routes, content)
    with open(file_path, 'w') as f:
        f.write(content)
    print("Added missing dashboard routes via regex target 2.")
else:
    print("Routes already present or target line not found via regex target 2.")
