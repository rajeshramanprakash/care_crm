<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorsWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorWalletController extends Controller
{
    public function index()
    {
        // Check if user is admin
        $user = Auth::user();
        $roleIds = explode(',', $user->role_id);

        if (!in_array('1', $roleIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $wallets = VendorsWallet::with(['user:id,f_name,l_name'])
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

    public function getVendorUsers()
    {
        // Check if user is admin
        $user = Auth::user();
        $roleIds = explode(',', $user->role_id);

        if (!in_array('1', $roleIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $users = User::whereRaw("FIND_IN_SET(?, role_id)", [10])
            ->select('id', 'f_name', 'l_name', 'wallet')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        // Check if user is admin
        $user = Auth::user();
        $roleIds = explode(',', $user->role_id);

        if (!in_array('1', $roleIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $wallet = VendorsWallet::find($id);

        if (!$wallet) {
            return response()->json(['success' => false, 'message' => 'Wallet not found'], 404);
        }

        $user = User::find($wallet->user_id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $status = $request->input('status');

        // Update user wallet based on status change
        if ($wallet->is_approved == 1 && $status != 1) {
            // Was approved, now not approved - subtract amount
            $user->wallet -= $wallet->ammount;
        } elseif ($wallet->is_approved != 1 && $status == 1) {
            // Was not approved, now approved - add amount
            $user->wallet += $wallet->ammount;
        }

        if ($user->save()) {
            $wallet->is_approved = $status;
            $wallet->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully!'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to update status']);
    }

    public function delete($id)
    {
        // Check if user is admin
        $user = Auth::user();
        $roleIds = explode(',', $user->role_id);

        if (!in_array('1', $roleIds)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $wallet = VendorsWallet::find($id);

        if (!$wallet) {
            return response()->json(['success' => false, 'message' => 'Wallet not found'], 404);
        }

        $user = User::find($wallet->user_id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // If wallet was approved, subtract amount from user wallet
        if ($wallet->is_approved == 1) {
            $user->wallet -= $wallet->ammount;
            $user->save();
        }

        $wallet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wallet deleted successfully!'
        ]);
    }
}
