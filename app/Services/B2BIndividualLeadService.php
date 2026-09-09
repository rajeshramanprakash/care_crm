<?php

namespace App\Services;

use App\Facades\UserAssignment;
use App\Models\B2BLead;
use App\Models\B2BUser;
use App\Models\Lead;
use App\Models\User;

class B2BIndividualLeadService
{
    /** @return array<string, mixed> */
    public function validateSingleInput(array $input): array
    {
        $input['number'] = $this->normalizeMobile((string) ($input['number'] ?? $input['mobile'] ?? ''));
        if (! isset($input['service']) && isset($input['service_requirement'])) {
            $input['service'] = $input['service_requirement'];
        }
        if (! isset($input['bulk']) && isset($input['bulk_qty'])) {
            $input['bulk'] = $input['bulk_qty'];
        }
        if (isset($input['service']) && trim((string) $input['service']) === '') {
            $input['service'] = null;
        }
        if (isset($input['detail']) && trim((string) $input['detail']) === '') {
            $input['detail'] = null;
        }

        return validator($input, [
            'number' => ['required', 'regex:/^[0-9]{10}$/'],
            'service' => ['nullable', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:5000'],
            'bulk' => ['required', 'integer', 'min:1'],
        ])->validate();
    }

    public function createLead(B2BUser $b2bUser, array $input, string $source = 'manual'): B2BLead
    {
        $validated = $this->validateSingleInput($input);
        $mobile = $this->normalizeMobile((string) $validated['number']);
        $service = trim((string) ($validated['service'] ?? ''));
        $detail = trim((string) ($validated['detail'] ?? ''));
        $bulkQty = (int) $validated['bulk'];

        $salesLeadId = $this->createSalesLead(
            $b2bUser,
            $mobile,
            $service,
            $detail,
            $bulkQty
        );

        return B2BLead::create([
            'b2b_user_id' => $b2bUser->id,
            'lead_id' => $salesLeadId,
            'operation_lead_id' => null,
            'name' => $mobile,
            'mobile' => $mobile,
            'service_requirement' => $service !== '' ? $service : null,
            'detail' => $detail !== '' ? $detail : null,
            'bulk_qty' => $bulkQty,
            'source' => $source,
        ]);
    }

    protected function createSalesLead(
        B2BUser $b2bUser,
        string $mobile,
        string $service,
        string $detail,
        int $bulkQty
    ): ?int {
        $assignedExecutive = UserAssignment::getAssigningUser(2, null, 'web');
        if (! $assignedExecutive) {
            $assignedExecutive = User::query()
                ->whereRaw('FIND_IN_SET(role_id, "2")')
                ->orderBy('id')
                ->first();
        }

        $lead = Lead::create([
            'date' => now(),
            'executive' => $assignedExecutive?->id,
            'customer_name' => $mobile,
            'contact_no' => $mobile,
            'query' => $service !== '' ? $service : null,
            'query_remarks' => $detail !== '' ? $detail : null,
            'status_remarks' => 'B2B Individual · Bulk qty: '.$bulkQty,
            'lead_source' => $this->buildLeadSource($b2bUser),
            'contact_type' => 'call',
            'status' => 'follow-up',
            'stage' => 'active',
        ]);

        return $lead->id;
    }

    protected function normalizeMobile(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        return strlen($digits) === 10 ? $digits : '';
    }

    public function buildLeadSource(B2BUser $b2bUser): string
    {
        $label = trim((string) ($b2bUser->name ?? $b2bUser->company_name ?? ''));

        return $label !== '' ? ('b2b individual ('.$label.')') : 'b2b individual';
    }
}
