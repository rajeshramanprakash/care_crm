<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductivityExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DailyCaseCount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ProductivityController extends Controller
{
    public function getProductivityData(Request $request)
    {
        $selectedDate = $request->input('date', Carbon::now()->format('Y-m-d'));

        $productivity = DB::table('productivities')
            ->select(
                'users.id as user_id',
                'users.role_id',
                DB::raw('MAX(users.f_name) as user_name'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 2 THEN productivities.file_count ELSE 0 END) as sales'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 3 THEN productivities.file_count ELSE 0 END) as doctor'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 4 THEN productivities.file_count ELSE 0 END) as medicine'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 5 THEN productivities.file_count ELSE 0 END) as billing'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 6 THEN productivities.file_count ELSE 0 END) as lab'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 7 THEN productivities.file_count ELSE 0 END) as dispatch'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 11 THEN productivities.file_count ELSE 0 END) as subadmin'),
                DB::raw('SUM(productivities.file_count) as total_files'),
                DB::raw("
                CASE
                    WHEN SUM(productivities.file_count) < 5 THEN 'Absent'
                    WHEN SUM(productivities.file_count) BETWEEN 5 AND 11 THEN 'Halfday'
                    ELSE 'Present'
                END as status
            ")
            )
            ->leftJoin('users', 'users.id', '=', 'productivities.user_id')
            ->whereDate('productivities.date', $selectedDate)
            ->groupBy('users.id')
            ->orderBy('users.id', 'asc')
            ->get();

        // Fetch daily case counts for the selected date, indexed by role_id
        $caseCounts = DailyCaseCount::where('date', $selectedDate)
            ->get()
            ->keyBy('role_id')
            ->map(function($item) {
                return [
                    'main' => $item->main_claim_cases,
                    'post' => $item->post_claim_cases,
                    'post_two' => $item->post_two_claim_cases,
                ];
            });

        return response()->json([
            'data' => $productivity,
            'case_counts' => $caseCounts,
        ]);
    }

    public function index()
    {
        $roles = [2, 3, 4, 5, 6, 7, 11];
        $users = User::select('id', 'f_name')
            ->where(function ($query) use ($roles) {
                foreach ($roles as $role) {
                    $query->orWhereRaw("FIND_IN_SET(?, role_id)", [$role]);
                }
            })
            ->get();
        return view('admin.per_user_productivity', compact('users'));
    }

    public function getPerUserProductivity(Request $request)
    {
        $userId = $request->input('user_id');
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        if (!$userId) {
            return response()->json(['error' => 'User ID is required'], 400);
        }

        $productivity = DB::table('productivities')
            ->select(
                DB::raw('DATE(productivities.date) as day'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 2 THEN productivities.file_count ELSE 0 END) as sales'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 3 THEN productivities.file_count ELSE 0 END) as doctor'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 5 THEN productivities.file_count ELSE 0 END) as billing'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 4 THEN productivities.file_count ELSE 0 END) as medicine'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 6 THEN productivities.file_count ELSE 0 END) as lab'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 7 THEN productivities.file_count ELSE 0 END) as dispatch'),
                DB::raw('SUM(CASE WHEN productivities.role_id = 11 THEN productivities.file_count ELSE 0 END) as subadmin'),

                DB::raw('SUM(productivities.file_count) as total_files'),
                DB::raw("
                CASE
                    WHEN SUM(productivities.file_count) < 5 THEN 'Absent'
                    WHEN SUM(productivities.file_count) BETWEEN 5 AND 11 THEN 'Halfday'
                    ELSE 'Present'
                END as status
            ")

            )
            ->where('productivities.user_id', $userId)
            ->whereMonth('productivities.date', Carbon::parse($month)->month)
            ->whereYear('productivities.date', Carbon::parse($month)->year)
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->get();

        return response()->json(['data' => $productivity]);
    }

    public function exportProductivity(Request $request)
{
    $userId = $request->input('user_id');
    $month = $request->input('month');

    if (!$userId || !$month) {
        return redirect()->back()->withErrors(['error' => 'User and Month are required for export.']);
    }

    return Excel::download(new ProductivityExport($userId, $month), 'productivity.xlsx');
}


}
