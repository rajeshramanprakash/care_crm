<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\DoctorReferralUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait ResolvesDoctorReferralPortalUser
{
    protected function doctorReferralFromRequest(Request $request): DoctorReferralUser
    {
        $info = Cache::get('doctor_referral_info_' . $request->user()->id);
        abort_if(! $info || empty($info['doctor_referral_user_id']), 403, 'Doctor referral access only.');

        $refUser = DoctorReferralUser::find((int) $info['doctor_referral_user_id']);
        abort_if(! $refUser || ! $refUser->is_active, 403, 'Doctor referral account inactive or not found.');

        return $refUser;
    }
}
