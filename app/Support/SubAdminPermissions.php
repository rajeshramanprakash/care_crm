<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubAdminPermissions
{
    public const SUB_ADMIN_ROLE_ID = 7;

    public const ADMIN_ROLE_ID = 1;

    /** Roles a Sub Admin may never assign or manage. */
    public const PROTECTED_ROLE_IDS = [self::ADMIN_ROLE_ID, self::SUB_ADMIN_ROLE_ID];

    /**
     * @return array<string, array<string, string>>
     */
    public static function modules(): array
    {
        return config('subadmin_permissions.modules', []);
    }

    /**
     * @return list<string>
     */
    public static function allPermissionNames(): array
    {
        $names = [];
        foreach (self::modules() as $perms) {
            foreach ($perms as $name) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Any create/edit/delete/view-details permission also needs the module's access permission.
     *
     * @param  array<int, string>  $permissions
     * @return list<string>
     */
    public static function normalize(array $permissions): array
    {
        $permissions = array_values(array_filter(array_map('strval', $permissions)));
        foreach (self::modules() as $perms) {
            $access = $perms['access'] ?? null;
            if ($access === null) {
                continue;
            }
            $others = array_diff(array_values($perms), [$access]);
            if (array_intersect($others, $permissions)) {
                $permissions[] = $access;
            }
        }

        return array_values(array_unique($permissions));
    }

    public static function isSubAdminSession(): bool
    {
        return (int) session('logged_role') === self::SUB_ADMIN_ROLE_ID;
    }

    /**
     * Permission needed for a subadmin.* route, or null when the route is not allowed for Sub Admins.
     */
    public static function requiredFor(Request $request): ?string
    {
        $name = (string) $request->route()?->getName();

        if ($name === 'subadmin.users.manage_process') {
            return $request->route('id') ? 'edit_user' : 'create_user';
        }

        foreach (config('subadmin_permissions.routes', []) as $pattern => $permission) {
            if (Str::is($pattern, $name)) {
                return $permission;
            }
        }

        return null;
    }

    /**
     * First page this Sub Admin is allowed to open (sidebar order).
     */
    public static function landingRoute(User $user): ?string
    {
        foreach (config('subadmin_permissions.landing_routes', []) as $permission => $routeName) {
            if ($user->can($permission)) {
                return $routeName;
            }
        }

        return null;
    }

    public static function userHasProtectedRole(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return self::containsProtectedRole(explode(',', (string) $user->getRawOriginal('role_id')));
    }

    /**
     * @param  array<int, mixed>  $roleIds
     */
    public static function containsProtectedRole(array $roleIds): bool
    {
        $roleIds = array_map('intval', array_filter($roleIds, fn ($id) => $id !== '' && $id !== null));

        return (bool) array_intersect($roleIds, self::PROTECTED_ROLE_IDS);
    }
}
