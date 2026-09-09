<?php

namespace App\Http\Controllers\Insurer;

use App\Http\Controllers\Controller;
use App\Models\CorporateEmployee;
use App\Models\CorporateUser;
use App\Models\InsurerUser;

class InsurerCorporateEmployeeController extends Controller
{
    public function index(CorporateUser $corporate)
    {
        $insurer = InsurerUser::find(session('insurer_user_id'));
        if (! $insurer || ! $insurer->is_active) {
            return redirect()->route('insurer.login')->with('error', 'Session expired.');
        }

        if ($corporate->owner_type !== 'insurer' || (int) $corporate->insurer_user_id !== (int) $insurer->id) {
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
            'partner_type' => 'insurer',
            'routePrefix' => 'insurer',
        ]);
    }
}
