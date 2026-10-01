<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesBulkPricingRules;
use App\Http\Controllers\Controller;

class BulkRegistrationController extends Controller
{
    use HandlesBulkPricingRules;

    public function __construct()
    {
        $this->middleware('can:view_bulk_registration');
    }

    protected function bulkRoutePrefix(): string
    {
        return 'admin';
    }
}
