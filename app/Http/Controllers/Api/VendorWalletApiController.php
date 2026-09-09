<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorsWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VendorWalletApiController extends Controller
{
    public function index()
    {
        // Check if user is vendor
        $user = Auth::user();
        $roleIds = explode(',', $user->role_id);

        if (!in_array('10', $roleIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $wallets = VendorsWallet::where('user_id', $user->id)
            ->with(['user:id,f_name,l_name'])
            ->orderBy('id', 'desc')
            ->get();

        $formattedWallets = $wallets->map(function ($wallet) {
            $paidByUser = User::find($wallet->paid_by);

            return [
                'id' => $wallet->id,
                'user' => [
                    'f_name' => $wallet->user->f_name ?? 'N/A',
                    'l_name' => $wallet->user->l_name ?? 'N/A'
                ],
                'paid_by_name' => $paidByUser ? $paidByUser->f_name : 'N/A',
                'ammount' => $wallet->ammount,
                'payment_type' => $wallet->payment_type,
                'payment_proof' => $wallet->payment_proof,
                'msg' => $wallet->msg,
                'is_approved' => $wallet->is_approved,
                'created_at' => $wallet->created_at->format('Y-m-d H:i:s')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedWallets
        ]);
    }

    public function store(Request $request)
    {
        // Check if user is vendor
        $user = Auth::user();
        $roleIds = explode(',', $user->role_id);

        if (!in_array('10', $roleIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'required|string|max:255',
            'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240', // 10MB max
            'msg' => 'nullable|string|max:500',
        ]);

        try {
            $wallet = new VendorsWallet();
            $wallet->user_id = $user->id;
            $wallet->paid_by = $user->id;
            $wallet->ammount = $request->amount;
            $wallet->payment_type = $request->payment_type;
            $wallet->msg = $request->msg;
            $wallet->is_approved = 0; // Default to pending

            if ($request->hasFile('payment_proof')) {
                $file = $request->file('payment_proof');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('payment_proofs', $filename, 'public');
                $wallet->payment_proof = $path;
            }

            $wallet->save();

            return response()->json([
                'success' => true,
                'message' => 'Wallet entry created successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create wallet entry: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStats()
    {
        // Check if user is vendor
        $user = Auth::user();
        $roleIds = explode(',', $user->role_id);

        if (!in_array('10', $roleIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $totalPaid = VendorsWallet::where('user_id', $user->id)
            ->where('is_approved', 1)
            ->sum('ammount');

        $totalCommission = \App\Models\VendorCase::where('user_id', $user->id)
            ->sum('commission');

        $walletBalance = $totalPaid - $totalCommission;

        return response()->json([
            'success' => true,
            'data' => [
                'total_paid' => $totalPaid,
                'total_commission' => $totalCommission,
                'wallet_balance' => $walletBalance
            ]
        ]);
    }
}
