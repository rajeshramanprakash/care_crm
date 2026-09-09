<?php

namespace App\Http\Controllers\TPA;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function index()
    {
        $page_heading = 'Wallets';
        $users = User::all(); // Fetch all users for the form
        return view('tpa.wallets.index', compact('page_heading', 'users'));
    }

    public function ajax()
    {
        $auth_user = Auth::guard('TPA')->user();
        $wallets = Wallet::where('user_id', $auth_user->id)->get();

        return datatables()->of($wallets)
            ->make(true);
    }
}
