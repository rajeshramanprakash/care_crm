<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BreakLog;
use App\Models\User;
use Illuminate\Http\Request;

class BreakLogController extends Controller
{
    public function index()
    {
        // Check if this is an API request
        if (request()->expectsJson()) {
            $breakLogs = BreakLog::with('user:id,f_name,l_name,profile_image')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($breakLogs);
        }

        $breakLogs = BreakLog::with('user')->orderBy('created_at', 'desc')->get();
        return view('admin.break_logs.index', compact('breakLogs'));
    }

    public function userLogs($id)
    {
        // Check if this is an API request
        if (request()->expectsJson()) {
            $user = User::findOrFail($id);
            $breakLogs = BreakLog::where('user_id', $id)
                ->with('user:id,f_name,l_name,profile_image')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'user' => $user,
                'breakLogs' => $breakLogs
            ]);
        }

        $user = User::findOrFail($id);
        $breakLogs = BreakLog::where('user_id', $id)->orderBy('created_at', 'desc')->get();
        return view('admin.break_logs.user_logs', compact('user', 'breakLogs'));
    }
}
