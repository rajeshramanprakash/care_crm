import re

file_path = 'routes/web.php'
with open(file_path, 'r') as f:
    content = f.read()

missing_routes = """
    Route::get('/dashboard/sales-leads-stats', [Controllers\\SubAdmin\\SubAdminController::class, 'getSalesLeadsStats'])->name('subadmin.dashboard.sales-leads-stats');
    Route::get('/dashboard/operation-leads-stats', [Controllers\\SubAdmin\\SubAdminController::class, 'getOperationLeadsStats'])->name('subadmin.dashboard.operation-leads-stats');
    Route::get('/dashboard/users-stats', [Controllers\\SubAdmin\\SubAdminController::class, 'getUsersStats'])->name('subadmin.dashboard.users-stats');
    Route::get('/dashboard/user-details/{userId}', [Controllers\\SubAdmin\\SubAdminController::class, 'getUserDetails'])->name('subadmin.dashboard.user-details');
    Route::get('/dashboard/sales-executives', [Controllers\\SubAdmin\\SubAdminController::class, 'getSalesExecutives'])->name('subadmin.dashboard.sales-executives');
    Route::get('/dashboard/operation-executives', [Controllers\\SubAdmin\\SubAdminController::class, 'getOperationExecutives'])->name('subadmin.dashboard.operation-executives');
    Route::get('/dashboard/users', [Controllers\\SubAdmin\\SubAdminController::class, 'getUsers'])->name('subadmin.dashboard.users');

    // Task Management Routes
    Route::get('/dashboard/tasks', [Controllers\\SubAdmin\\SubAdminController::class, 'getTasks'])->name('subadmin.dashboard.tasks');
    Route::post('/dashboard/tasks', [Controllers\\SubAdmin\\SubAdminController::class, 'storeTask'])->name('subadmin.dashboard.tasks.store');
    Route::get('/dashboard/tasks/{id}/edit', [Controllers\\SubAdmin\\SubAdminController::class, 'editTask'])->name('subadmin.dashboard.tasks.edit');
    Route::put('/dashboard/tasks/{id}', [Controllers\\SubAdmin\\SubAdminController::class, 'updateTask'])->name('subadmin.dashboard.tasks.update');
    Route::delete('/dashboard/tasks/{id}', [Controllers\\SubAdmin\\SubAdminController::class, 'destroyTask'])->name('subadmin.dashboard.tasks.destroy');
    Route::get('/dashboard/task-history', [Controllers\\SubAdmin\\SubAdminController::class, 'getTaskHistory'])->name('subadmin.dashboard.task-history');
    Route::get('/dashboard/calendar-data', [Controllers\\SubAdmin\\SubAdminController::class, 'getCalendarData'])->name('subadmin.dashboard.calendar-data');
    Route::get('/dashboard/calendar-leads', [Controllers\\SubAdmin\\SubAdminController::class, 'getCalendarLeads'])->name('subadmin.dashboard.calendar-leads');
    Route::get('/dashboard/revenue-stats', [Controllers\\SubAdmin\\SubAdminController::class, 'getRevenueStats'])->name('subadmin.dashboard.revenue-stats');
    Route::get('/dashboard/pending-deployments', [Controllers\\SubAdmin\\SubAdminController::class, 'getPendingDeployments'])->name('subadmin.dashboard.pending-deployments');
    Route::get('/dashboard/outstanding-amount-stats', [Controllers\\SubAdmin\\SubAdminController::class, 'getOutstandingAmountStats'])->name('subadmin.dashboard.outstanding-amount-stats');
    Route::get('/dashboard/profile-pending-stats', [Controllers\\SubAdmin\\SubAdminController::class, 'getProfilePendingStats'])->name('subadmin.dashboard.profile-pending-stats');
    Route::get('/dashboard/vendor-payment-stats', [Controllers\\SubAdmin\\SubAdminController::class, 'getVendorPaymentStats'])->name('subadmin.dashboard.vendor-payment-stats');
    Route::get('/dashboard/unverified-deployment-payments-stats', [Controllers\\SubAdmin\\SubAdminController::class, 'getUnverifiedDeploymentPaymentsStats'])->name('subadmin.dashboard.unverified-deployment-payments-stats');
    Route::get('/dashboard/pending-callbacks', [Controllers\\SubAdmin\\SubAdminController::class, 'getPendingCallbacks'])->name('subadmin.dashboard.pending-callbacks');
    
    // Recent Calls Routes
    Route::get('/dashboard/recent-calls/sales', [Controllers\\SubAdmin\\SubAdminController::class, 'getRecentCallsSales'])->name('subadmin.dashboard.recent-calls.sales');
    Route::get('/dashboard/recent-calls/operation', [Controllers\\SubAdmin\\SubAdminController::class, 'getRecentCallsOperation'])->name('subadmin.dashboard.recent-calls.operation');
"""

# Insert right after Route::get('/dashboard', [Controllers\SubAdmin\SubAdminController::class, 'dashboard'])->name('subadmin.dashboard');
target_line = "Route::get('/dashboard', [Controllers\\SubAdmin\\SubAdminController::class, 'dashboard'])->name('subadmin.dashboard');"
if "getUnverifiedDeploymentPaymentsStats" not in content and target_line in content:
    content = content.replace(target_line, target_line + "\n" + missing_routes)
    with open(file_path, 'w') as f:
        f.write(content)
    print("Added missing dashboard routes.")
else:
    print("Routes already present or target line not found.")
