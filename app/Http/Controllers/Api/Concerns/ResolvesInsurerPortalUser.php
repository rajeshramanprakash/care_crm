<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\InsurerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait ResolvesInsurerPortalUser
{
    protected function insurerFromRequest(Request $request): InsurerUser
    {
        $info = Cache::get('insurer_info_' . $request->user()->id);
        abort_if(! $info || empty($info['insurer_user_id']), 403, 'Insurer access only.');

        $insurer = InsurerUser::find((int) $info['insurer_user_id']);
        abort_if(! $insurer || ! $insurer->is_active, 403, 'Insurer account inactive or not found.');

        return $insurer;
    }
}
