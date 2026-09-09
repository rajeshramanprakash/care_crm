<?php

namespace App\Http\Controllers\B2B\Concerns;

use App\Models\B2BUser;

trait ResolvesB2BPartnerPortal
{
    protected function portalRoutePrefix(B2BUser $user): string
    {
        return match ($user->account_type) {
            B2BUser::TYPE_CORPORATE => 'b2b.corporate',
            B2BUser::TYPE_INDIVIDUAL => 'b2b.individual',
            default => 'b2b',
        };
    }

    protected function portalUrlPrefix(B2BUser $user): string
    {
        return match ($user->account_type) {
            B2BUser::TYPE_CORPORATE => '/b2b-corporate',
            B2BUser::TYPE_INDIVIDUAL => '/b2b-individual',
            default => '/b2b',
        };
    }

    protected function portalLayout(B2BUser $user): string
    {
        return match ($user->account_type) {
            B2BUser::TYPE_CORPORATE => 'b2b_corporate.layouts.app',
            B2BUser::TYPE_INDIVIDUAL => 'b2b_individual.layouts.app',
            default => 'b2b.layouts.app',
        };
    }

    protected function portalDashboardView(B2BUser $user): string
    {
        return match ($user->account_type) {
            B2BUser::TYPE_CORPORATE => 'b2b_corporate.dashboard',
            B2BUser::TYPE_INDIVIDUAL => 'b2b_individual.dashboard',
            default => 'b2b.dashboard',
        };
    }

    protected function portalContext(B2BUser $user): array
    {
        return [
            'portal_layout' => $this->portalLayout($user),
            'portal_route_prefix' => $this->portalRoutePrefix($user),
            'portal_url_prefix' => $this->portalUrlPrefix($user),
        ];
    }

    protected function currentB2BUser(): ?B2BUser
    {
        $id = session('b2b_user_id');

        return $id ? B2BUser::query()->find($id) : null;
    }
}
