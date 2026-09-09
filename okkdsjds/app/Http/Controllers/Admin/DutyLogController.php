<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DutyLogs;
use App\Models\User;
use Illuminate\Http\Request;

class DutyLogController extends Controller
{
    public function index()
    {
        // Check if this is an API request
        if (request()->expectsJson()) {
            $dutyLogs = DutyLogs::with('user:id,f_name,l_name,profile_image')
                ->orderBy('created_at', 'desc')
                ->get();

            \Illuminate\Support\Facades\Log::info("API Duty Logs Request - Count: " . $dutyLogs->count());
            foreach ($dutyLogs as $log) {
                \Illuminate\Support\Facades\Log::info("Log ID: {$log->id}, User: {$log->user->f_name} {$log->user->l_name}, Status: {$log->break_status}, Start: {$log->break_start_time}, End: {$log->break_end_time}");
            }

            return response()->json($dutyLogs);
        }

        $dutyLogs = DutyLogs::with('user')->orderBy('created_at', 'desc')->get();
        return view('admin.duty_logs.index', compact('dutyLogs'));
    }

    public function userLogs($id)
    {
        // Check if this is an API request
        if (request()->expectsJson()) {
            $user = User::findOrFail($id);
            $dutyLogs = DutyLogs::where('user_id', $id)
                ->with('user:id,f_name,l_name,profile_image')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'user' => $user,
                'dutyLogs' => $dutyLogs
            ]);
        }

        $user = User::findOrFail($id);
        $dutyLogs = DutyLogs::where('user_id', $id)->orderBy('created_at', 'desc')->get();
        return view('admin.duty_logs.user_logs', compact('user', 'dutyLogs'));
    }
}
