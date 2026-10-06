<?php

namespace App\Support\LeadAi;

use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * AI lead review is shown only to Admin (all leads), Sales Manager (sales leads of own team)
 * and Operation Manager (operation leads of own team).
 */
class LeadAiAccess
{
    public const ADMIN = 1;

    public const SALES_MANAGER = 3;

    public const OPERATION_MANAGER = 5;

    public static function role(): int
    {
        return (int) session('logged_role');
    }

    /** Lead types the current user may see; empty = none. */
    public static function types(): array
    {
        return match (self::role()) {
            self::ADMIN => ['sales', 'operation'],
            self::SALES_MANAGER => ['sales'],
            self::OPERATION_MANAGER => ['operation'],
            default => [],
        };
    }

    public static function canView(?User $user, string $type, int $leadId): bool
    {
        if (! $user || ! in_array($type, self::types(), true)) {
            return false;
        }
        if (self::role() === self::ADMIN) {
            return true;
        }

        $query = $type === 'operation' ? OperationLead::withTrashed() : Lead::query();

        return self::scopeTeam($query, $user)->whereKey($leadId)->exists();
    }

    /** Leads whose executive is the manager or someone reporting to the manager. */
    public static function scopeTeam(Builder $query, User $user, string $column = 'executive'): Builder
    {
        $team = User::withTrashed()->where('parent_id', $user->id)->pluck('id')->push($user->id)->map(fn ($id) => (string) $id)->all();

        return $query->whereIn($column, $team);
    }

    public static function routePrefix(): ?string
    {
        return match (self::role()) {
            self::ADMIN => 'admin',
            self::SALES_MANAGER => 'manager',
            self::OPERATION_MANAGER => 'operation-manager',
            default => null,
        };
    }

    public static function layout(): string
    {
        return match (self::role()) {
            self::SALES_MANAGER => 'manager.layouts.app',
            self::OPERATION_MANAGER => 'operation_manager.layouts.app',
            default => 'admin.layouts.app',
        };
    }
}
