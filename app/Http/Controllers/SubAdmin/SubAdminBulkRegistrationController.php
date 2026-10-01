<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Concerns\HandlesBulkPricingRules;
use App\Http\Controllers\Controller;

class SubAdminBulkRegistrationController extends Controller
{
    use HandlesBulkPricingRules;

    public function __construct()
    {
        $this->middleware('can:view_bulk_registration');
    }

    protected function bulkRoutePrefix(): string
    {
        return 'subadmin';
    }
}
