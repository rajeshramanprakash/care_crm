<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorsWallet;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{

    public function index()
    {
        $page_heading = 'Tpa Wallets';
        $users = User::whereRaw("FIND_IN_SET(?, role_id)", [8])->get();
        return view('admin.wallets.index', compact('page_heading', 'users'));
    }

    public function ajax()
    {
        $wallets = Wallet::with(['user:id,f_name,l_name'])->get();

        return datatables()->of($wallets)
            ->addColumn('paid_by_name', function ($wallet) {
                $paidByUser = User::find($wallet->paid_by);
                return $paidByUser ? $paidByUser->f_name : 'N/A';
            })
            ->addColumn('actions', function ($wallet) {
                return '<button class="btn btn-sm btn-danger delete-wallet" data-id="' . $wallet->id . '">Delete</button>';
            })
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'amount' => 'required|numeric',
            'payment_proof' => 'required|file',
            'payment_type' => 'required|string',
        ]);

        $auth_user = Auth::guard('Admin')->user();

        $user = User::where('id', $request->user_id)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $user->wallet -= $request->amount;

        $wallet = new Wallet();
        $wallet->user_id = $request->user_id;
        $wallet->paid_by = $auth_user->id;
        $wallet->ammount = $request->amount;
        $wallet->payment_type = $request->payment_type;
        $wallet->msg = $request->msg;

        if ($request->hasFile('payment_proof')) {
            $wallet->payment_proof = $request->file('payment_proof')->store('payment_proofs', 'public');
        }

        $wallet->save();
        $user->save();

        return response()->json(['success' => true, 'message' => 'Wallet entry created successfully!']);
    }

    public function delete_tpa_wallet($tpa_wallet_id){
        $wallet = Wallet::where('id', $tpa_wallet_id)->first();
        $user = User::where('id', $wallet->user_id)->first();
        $user->wallet += $wallet->ammount;
        if($user->save()){
            $wallet->delete();
        }
        return redirect()->route('admin.wallets.index')->with('status', [
            'alert_type' => 'success',
            'message' => 'Wallet deleted',
        ]);
    }

    public function index_vendor()
    {
        $page_heading = 'Vendor Wallets';
        $users = User::whereRaw("FIND_IN_SET(?, role_id)", [10])->get();
        return view('admin.wallets.vendor.index', compact('page_heading', 'users'));
    }

    public function ajax_vendor()
    {
        $wallets = VendorsWallet::with(['user:id,f_name,l_name'])->get();

        return datatables()->of($wallets)
            ->addColumn('paid_by_name', function ($wallet) {
                $paidByUser = User::find($wallet->paid_by);
                return $paidByUser ? $paidByUser->f_name : 'N/A';
            })
            ->addColumn('actions', function ($wallet) {
                return '<button class="btn btn-sm btn-danger delete-wallet" data-id="' . $wallet->id . '">Delete</button>';
            })
            ->make(true);
    }

    public function vendor_update_status(Request $request)
    {
        $wallet = VendorsWallet::find($request->id);
        $user = User::where('id', $wallet->user_id)->first();

        if ($wallet->is_approved == 1 && $request->status != 1) {
            $user->wallet -= $wallet->ammount;
        } elseif ($wallet->is_approved != 1 && $request->status == 1) {
            $user->wallet += $wallet->ammount;
        }

        if ($wallet && $user->save()) {
            $wallet->is_approved = $request->status;
            $wallet->save();
            return response()->json(['success' => true, 'message' => 'Status updated successfully!']);
        }

        return response()->json(['success' => false, 'message' => 'Wallet not found']);
    }

    public function delete_vendor_wallet($vendor_wallet_id){
        $wallet = VendorsWallet::where('id', $vendor_wallet_id)->first();
        $user = User::where('id', $wallet->user_id)->first();
        $user->wallet -= $wallet->ammount;
        if($user->save()){
            $wallet->delete();
        }
        return redirect()->route('admin.wallets.index')->with('status', [
            'alert_type' => 'success',
            'message' => 'vendor Wallet deleted',
        ]);
    }
}
