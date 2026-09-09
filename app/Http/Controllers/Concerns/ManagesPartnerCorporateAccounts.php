<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CorporateUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait ManagesPartnerCorporateAccounts
{
    abstract protected function partnerOwnerType(): string;

    abstract protected function partnerOwnerId(): ?int;

    protected function corporateQuery()
    {
        $type = $this->partnerOwnerType();
        $id = $this->partnerOwnerId();
        $column = $type === 'broker' ? 'broker_user_id' : 'insurer_user_id';

        return CorporateUser::query()->where('owner_type', $type)->where($column, $id);
    }

    protected function validateCorporatePayload(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'corporate_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('corporate_users', 'username')->ignore($ignoreId)],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($ignoreId) {
            $rules['password'] = ['nullable', 'string', 'min:6', 'max:100'];
        } else {
            $rules['password'] = ['required', 'string', 'min:6', 'max:100'];
        }

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    protected function assignOwner(array $data): array
    {
        $type = $this->partnerOwnerType();
        $id = $this->partnerOwnerId();
        $data['owner_type'] = $type;
        $data['insurer_user_id'] = $type === 'insurer' ? $id : null;
        $data['broker_user_id'] = $type === 'broker' ? $id : null;

        return $data;
    }

    protected function findOwnedCorporate(int $id): CorporateUser
    {
        return $this->corporateQuery()->whereKey($id)->firstOrFail();
    }
}
