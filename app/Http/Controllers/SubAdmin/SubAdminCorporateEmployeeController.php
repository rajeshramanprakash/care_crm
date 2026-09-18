<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Models\CorporateEmployee;
use Illuminate\Http\Request;

class SubAdminCorporateEmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = CorporateEmployee::query()
            ->with([
                'corporate' => fn ($q) => $q->with([
                    'insurer:id,name,company_name',
                    'broker:id,name,company_name',
                ]),
            ])
            ->orderByDesc('id');

        if ($request->filled('corporate_id')) {
            $query->where('corporate_user_id', $request->integer('corporate_id'));
        }
        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('employee_id', 'like', $q)
                    ->orWhere('employee_name', 'like', $q)
                    ->orWhere('email', 'like', $q)
                    ->orWhere('phone_number', 'like', $q);
            });
        }

        $items = $query->paginate(25)->withQueryString();

        return view('subadmin.corporate-employees.index', compact('items'));
    }
}
