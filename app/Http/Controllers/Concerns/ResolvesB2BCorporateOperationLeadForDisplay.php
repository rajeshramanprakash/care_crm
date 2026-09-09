<?php

namespace App\Http\Controllers\Concerns;

use App\Models\B2BLead;
use App\Models\OperationLead;

trait ResolvesB2BCorporateOperationLeadForDisplay
{
    protected function b2bCorporateLeadForOperationShow(OperationLead $lead): ?B2BLead
    {
        return B2BLead::corporateLeadForOperationLead((int) $lead->id);
    }
}
