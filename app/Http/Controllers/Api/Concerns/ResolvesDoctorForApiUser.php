<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\DoctorRequest;
use Illuminate\Support\Facades\Cache;

trait ResolvesDoctorForApiUser
{
    protected function doctorForUser($user): ?DoctorRequest
    {
        if (!$user) {
            return null;
        }
        $cached = Cache::get('doctor_reg_info_'.$user->id);
        if ($cached && !empty($cached['doctor_request_id'])) {
            $d = DoctorRequest::find($cached['doctor_request_id']);
            if ($d && $d->approval_status === 'approved') {
                return $d;
            }
        }
        $mobile = $user->mobile;

        return DoctorRequest::query()
            ->where(function ($q) use ($mobile) {
                $q->where('mobile', $mobile)->orWhere('contact_no', $mobile);
            })
            ->where('approval_status', 'approved')
            ->first();
    }
}
