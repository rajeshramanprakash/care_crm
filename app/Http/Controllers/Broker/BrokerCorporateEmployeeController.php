<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Models\BrokerUser;
use App\Models\CorporateEmployee;
use App\Models\CorporateUser;

class BrokerCorporateEmployeeController extends Controller
{
    public function index(CorporateUser $corporate)
    {
        $broker = BrokerUser::find(session('broker_user_id'));
        if (! $broker || ! $broker->is_active) {
            return redirect()->route('broker.login')->with('error', 'Session expired.');
        }

        if ($corporate->owner_type !== 'broker' || (int) $corporate->broker_user_id !== (int) $broker->id) {
            abort(404);
        }

        $corporate->load(['insurer:id,name,company_name', 'broker:id,name,company_name']);

        $items = CorporateEmployee::query()
            ->where('corporate_user_id', $corporate->id)
            ->orderByDesc('id')
            ->get()
            ->each(fn ($emp) => $emp->setRelation('corporate', $corporate));

        return view('partner-portal.corporate-employees.index', [
            'items' => $items,
            'corporate' => $corporate,
            'partner_type' => 'broker',
            'routePrefix' => 'broker',
        ]);
    }
}
