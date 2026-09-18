<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Models\CorporateUser;
use Illuminate\Http\Request;

class SubAdminCorporateUserController extends Controller
{
    public function index(Request $request)
    {
        $query = CorporateUser::query()
            ->with([
                'insurer:id,name,company_name',
                'broker:id,name,company_name',
            ])
            ->withCount('employees')
            ->orderByDesc('id');

        if ($request->filled('owner_type')) {
            $query->where('owner_type', $request->string('owner_type'));
        }
        if ($request->filled('broker_id')) {
            $query->where('owner_type', 'broker')->where('broker_user_id', $request->integer('broker_id'));
        }
        if ($request->filled('insurer_id')) {
            $query->where('owner_type', 'insurer')->where('insurer_user_id', $request->integer('insurer_id'));
        }
        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('corporate_name', 'like', $q)
                    ->orWhere('username', 'like', $q);
            });
        }

        $items = $query->paginate(25)->withQueryString();

        $totalQuery = CorporateUser::query()
            ->when($request->filled('owner_type'), fn ($q) => $q->where('owner_type', $request->string('owner_type')))
            ->when($request->filled('broker_id'), fn ($q) => $q->where('owner_type', 'broker')->where('broker_user_id', $request->integer('broker_id')))
            ->when($request->filled('insurer_id'), fn ($q) => $q->where('owner_type', 'insurer')->where('insurer_user_id', $request->integer('insurer_id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.$request->string('q').'%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('corporate_name', 'like', $like)->orWhere('username', 'like', $like);
                });
            });
        $totalEmployees = (int) $totalQuery->withCount('employees')->get()->sum('employees_count');

        return view('subadmin.corporate-accounts.index', compact('items', 'totalEmployees'));
    }
}
