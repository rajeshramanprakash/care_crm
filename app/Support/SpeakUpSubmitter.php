<?php

namespace App\Support;

use App\Models\JobRequest;
use App\Models\Role;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Who is submitting to Speak Up: CRM staff (by logged role), a freelancer or a vendor (session logins).
 */
class SpeakUpSubmitter
{
    /** logged_role id => layout */
    public const STAFF_LAYOUTS = [
        2 => 'sales.layouts.app',
        3 => 'manager.layouts.app',
        4 => 'operation.layouts.app',
        5 => 'operation_manager.layouts.app',
        7 => 'admin.layouts.app',
    ];

    /**
     * @return array{type: string, id: int, name: string, role: string, layout: string}|null
     */
    public static function resolve(?string $preferred = null): ?array
    {
        $candidates = [
            'staff' => fn () => static::staff(),
            'freelancer' => fn () => static::freelancer(),
            'vendor' => fn () => static::vendor(),
        ];

        if ($preferred && isset($candidates[$preferred]) && ($found = $candidates[$preferred]())) {
            return $found;
        }

        foreach ($candidates as $resolver) {
            if ($found = $resolver()) {
                return $found;
            }
        }

        return null;
    }

    private static function staff(): ?array
    {
        $user = Auth::user();
        $roleId = (int) Session::get('logged_role');
        if (! $user || ! isset(self::STAFF_LAYOUTS[$roleId])) {
            return null;
        }

        return [
            'type' => 'staff',
            'id' => (int) $user->id,
            'name' => trim($user->f_name . ' ' . $user->l_name),
            'role' => (string) (Role::find($roleId)->name ?? 'Staff'),
            'layout' => self::STAFF_LAYOUTS[$roleId],
        ];
    }

    private static function freelancer(): ?array
    {
        $id = Session::get('freelancer_id');
        if (! $id || ! ($freelancer = JobRequest::find($id))) {
            return null;
        }

        return [
            'type' => 'freelancer',
            'id' => (int) $freelancer->id,
            'name' => (string) ($freelancer->name ?? Session::get('freelancer_name', 'Freelancer')),
            'role' => 'Freelancer',
            'layout' => 'freelancer.layouts.app',
        ];
    }

    private static function vendor(): ?array
    {
        $id = Session::get('vendor_id');
        if (! $id || Session::get('login_type') !== 'vendor' || ! ($vendor = Vendor::find($id))) {
            return null;
        }

        return [
            'type' => 'vendor',
            'id' => (int) $vendor->id,
            'name' => (string) ($vendor->name ?? Session::get('vendor_name', 'Vendor')),
            'role' => 'Vendor',
            'layout' => 'vendor.layouts.app',
        ];
    }
}
