<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubAdminActivityLog;
use Illuminate\Http\Request;

class SubAdminActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = SubAdminActivityLog::query()
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->module))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(fn ($sq) => $sq->where('description', 'like', $term)->orWhere('url', 'like', $term));
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $subAdmins = SubAdminActivityLog::query()
            ->select('user_id', 'user_name')
            ->groupBy('user_id', 'user_name')
            ->orderBy('user_name')
            ->get();

        $modules = SubAdminActivityLog::query()->distinct()->orderBy('module')->pluck('module')->filter();
        $actions = ['create' => 'Create', 'update' => 'Update', 'delete' => 'Delete', 'other' => 'Other'];

        return view('admin.subadmin_activity.index', compact('logs', 'subAdmins', 'modules', 'actions'));
    }

    public function show(SubAdminActivityLog $log)
    {
        return view('admin.subadmin_activity.show', compact('log'));
    }
}
