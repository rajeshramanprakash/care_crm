<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BrokerUser;
use App\Models\CorporateEmployee;
use App\Models\CorporateUser;
use App\Models\InsurerUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PortalAuthApiController extends Controller
{
    private function findOrCreatePortalUser(string $email, string $firstName, ?string $mobileHint = null): User
    {
        $user = User::withTrashed()->where('email', $email)->first();
        if (! $user) {
            $mobile = $this->resolveUniqueMobile($mobileHint, $email);
            $user = User::create([
                'email' => $email,
                'mobile' => $mobile,
                'f_name' => $firstName ?: 'Portal User',
                'l_name' => '',
                'password' => bcrypt(uniqid()),
                'role_id' => '0',
                'is_active' => 1,
            ]);
        } else {
            if ($user->trashed()) {
                $user->restore();
            }
            $user->f_name = $firstName ?: $user->f_name;
            $user->is_active = 1;
            $user->save();
        }

        return $user;
    }

    private function resolveUniqueMobile(?string $hint, string $emailSeed): string
    {
        $digits = preg_replace('/\D/', '', (string) $hint) ?: '';
        if (strlen($digits) >= 10) {
            $candidate = substr($digits, -10);
            if (! User::withTrashed()->where('mobile', $candidate)->exists()) {
                return $candidate;
            }
        }

        $hash = abs(crc32($emailSeed));
        for ($i = 0; $i < 100; $i++) {
            $candidate = '8' . str_pad((string) (($hash + $i) % 1000000000), 9, '0', STR_PAD_LEFT);
            if (! User::withTrashed()->where('mobile', $candidate)->exists()) {
                return $candidate;
            }
        }

        return '8' . substr((string) microtime(true), -9);
    }

    /** @param array<string, mixed> $portalPayload */
    private function issueToken(User $user, string $loginType, string $cacheKey, array $portalPayload, array $responsePortal): JsonResponse
    {
        $user->tokens()->where('name', 'mobile-app')->delete();
        $token = $user->createToken('mobile-app')->plainTextToken;
        Cache::put($cacheKey . $user->id, $portalPayload, now()->addDays(30));

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
            'login_type' => $loginType,
            'portal' => $responsePortal,
        ]);
    }

    public function loginInsurer(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $insurer = InsurerUser::query()
            ->where('username', $credentials['username'])
            ->first();

        if (! $insurer || ! $insurer->is_active || ! $insurer->checkPassword($credentials['password'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password, or account is inactive.',
            ], 401);
        }

        $email = 'insurer_portal_' . $insurer->id . '@carelix.com';
        $user = $this->findOrCreatePortalUser($email, $insurer->name, $insurer->mobile);
        $payload = [
            'insurer_user_id' => $insurer->id,
            'name' => $insurer->name,
            'company_name' => $insurer->company_name,
            'username' => $insurer->username,
            'mobile' => $insurer->mobile,
            'portal_label' => 'Insurer',
        ];

        return $this->issueToken($user, 'insurer', 'insurer_info_', $payload, $payload);
    }

    public function loginBroker(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $broker = BrokerUser::query()
            ->where('username', $credentials['username'])
            ->first();

        if (! $broker || ! $broker->is_active || ! $broker->checkPassword($credentials['password'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password, or account is inactive.',
            ], 401);
        }

        $email = 'broker_portal_' . $broker->id . '@carelix.com';
        $user = $this->findOrCreatePortalUser($email, $broker->name, $broker->mobile);
        $payload = [
            'broker_user_id' => $broker->id,
            'name' => $broker->name,
            'company_name' => $broker->company_name,
            'username' => $broker->username,
            'mobile' => $broker->mobile,
            'portal_label' => 'Broker',
        ];

        return $this->issueToken($user, 'broker', 'broker_info_', $payload, $payload);
    }

    public function loginCorporateEmployee(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'corporate_username' => ['required', 'string', 'max:100'],
            'employee_id' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $corporate = CorporateUser::query()
            ->where('username', $credentials['corporate_username'])
            ->where('is_active', true)
            ->first();

        if (! $corporate) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid corporate username or account is inactive.',
            ], 401);
        }

        $employee = CorporateEmployee::query()
            ->where('corporate_user_id', $corporate->id)
            ->where('employee_id', $credentials['employee_id'])
            ->first();

        if (! $employee || ! $employee->is_active || ! $employee->checkPassword($credentials['password'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid employee ID or password, or account is inactive.',
            ], 401);
        }

        $email = 'corp_employee_' . $employee->id . '@carelix.com';
        $user = $this->findOrCreatePortalUser($email, $employee->employee_name, null);
        $payload = [
            'corporate_employee_id' => $employee->id,
            'employee_id' => $employee->employee_id,
            'employee_name' => $employee->employee_name,
            'corporate_user_id' => $corporate->id,
            'corporate_name' => $corporate->corporate_name,
            'corporate_username' => $corporate->username,
            'portal_label' => 'Corporate Employee',
        ];

        return $this->issueToken($user, 'corporate_employee', 'corporate_employee_info_', $payload, $payload);
    }
}
