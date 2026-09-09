<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2BReferenceUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class B2BReferenceUserController extends Controller
{
    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10}$/', Rule::unique('b2b_reference_users', 'mobile')->ignore($ignoreId)],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        $data['is_active'] = true;
        B2BReferenceUser::create($data);

        return redirect()->route('admin.b2b_users.index')->with('status', [
            'alert_type' => 'success',
            'message' => 'B2B reference user created. They can log in with this mobile via OTP.',
        ]);
    }

    public function destroy(B2BReferenceUser $b2bReferenceUser)
    {
        $b2bReferenceUser->delete();

        return redirect()->route('admin.b2b_users.index')->with('status', [
            'alert_type' => 'success',
            'message' => 'Reference user removed. Linked B2B users no longer have a referral assignment.',
        ]);
    }

    public function apiStore(Request $request)
    {
        $data = $this->validatePayload($request);
        $data['is_active'] = true;
        $item = B2BReferenceUser::create($data);

        return response()->json([
            'success' => true,
            'message' => 'B2B reference user created successfully.',
            'item' => $item,
        ]);
    }

    public function apiDestroy(B2BReferenceUser $b2bReferenceUser)
    {
        $b2bReferenceUser->delete();

        return response()->json([
            'success' => true,
            'message' => 'B2B reference user removed successfully.',
        ]);
    }
}
