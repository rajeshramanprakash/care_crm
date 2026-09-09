<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\BrokerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait ResolvesBrokerPortalUser
{
    protected function brokerFromRequest(Request $request): BrokerUser
    {
        $info = Cache::get('broker_info_' . $request->user()->id);
        abort_if(! $info || empty($info['broker_user_id']), 403, 'Broker access only.');

        $broker = BrokerUser::find((int) $info['broker_user_id']);
        abort_if(! $broker || ! $broker->is_active, 403, 'Broker account inactive or not found.');

        return $broker;
    }
}
