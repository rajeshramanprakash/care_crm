<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesDoctorReferralPortalUser;
use App\Http\Controllers\Concerns\BuildsDoctorReferralPortalData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DoctorReferralPortalApiController extends Controller
{
    use BuildsDoctorReferralPortalData;
    use ResolvesDoctorReferralPortalUser;

    public function dashboard(Request $request)
    {
        $refUser = $this->doctorReferralFromRequest($request);
        $payload = $this->doctorReferralDashboardPayload($refUser);

        return response()->json($payload);
    }

    public function leads(Request $request)
    {
        $refUser = $this->doctorReferralFromRequest($request);
        $payload = $this->doctorReferralLeadsPayload($refUser);

        return response()->json($payload);
    }
}
