<?php

namespace App\Services;

use App\Facades\UserAssignment;
use App\Models\B2BLead;
use App\Models\B2BUser;
use App\Models\OperationLead;
use App\Models\User;
use App\Models\WhatsappMsgGroup;
use Illuminate\Support\Facades\Log;

class B2BCorporateLeadService
{
    /** @return array<string, mixed> */
    public function validateSingleInput(array $input): array
    {
        $input['number'] = $this->normalizeMobile((string) ($input['number'] ?? $input['mobile'] ?? ''));
        if ($input['number'] === '') {
            $input['number'] = null;
        }
        if (! isset($input['service']) && isset($input['service_requirement'])) {
            $input['service'] = $input['service_requirement'];
        }
        if (! isset($input['bulk']) && isset($input['bulk_qty'])) {
            $input['bulk'] = $input['bulk_qty'];
        }

        return validator($input, [
            'number' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'service' => ['required', 'string', 'max:255'],
            'detail' => ['required', 'string', 'max:5000'],
            'bulk' => ['required', 'integer', 'min:1'],
        ])->validate();
    }

    public function createLead(B2BUser $b2bUser, array $input, string $source = 'manual'): B2BLead
    {
        $validated = $this->validateSingleInput($input);
        $mobile = $this->normalizeMobile(
            (string) ($validated['number'] ?? $validated['mobile'] ?? '')
        );
        $service = trim((string) ($validated['service'] ?? $validated['service_requirement'] ?? ''));
        $detail = trim((string) ($validated['detail'] ?? ''));
        $bulkQty = (int) ($validated['bulk'] ?? $validated['bulk_qty'] ?? 0);

        $operationLead = $this->createOperationLead($b2bUser, $mobile, $service, $detail, $bulkQty);

        return B2BLead::create([
            'b2b_user_id' => $b2bUser->id,
            'lead_id' => null,
            'operation_lead_id' => $operationLead->id,
            'name' => $this->customerLabel($b2bUser),
            'mobile' => $mobile ?: null,
            'service_requirement' => $service,
            'detail' => $detail,
            'bulk_qty' => $bulkQty,
            'source' => $source,
        ]);
    }

    protected function createOperationLead(
        B2BUser $b2bUser,
        string $mobile,
        string $service,
        string $detail,
        int $bulkQty
    ): OperationLead {
        $assignedExecutive = UserAssignment::getAssigningUser(4, null, 'web');
        if (! $assignedExecutive) {
            $assignedExecutive = User::query()
                ->whereRaw('FIND_IN_SET(role_id, "4")')
                ->orderBy('id')
                ->first();
        }

        $nextNumber = ((int) (OperationLead::query()->max('id') ?? 0)) + 1;
        $leadId = 'CHO'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

        $operationLead = OperationLead::create([
            'lead_id' => $leadId,
            'date_time' => now(),
            'executive' => $assignedExecutive?->id,
            'customer_name' => $this->customerLabel($b2bUser),
            'contact_no' => $mobile ?: null,
            'query' => $service,
            'query_remark' => $detail,
            'status_remark' => 'B2B Corporate · Bulk qty: '.$bulkQty,
            'status' => 'follow-up',
        ]);

        if ($mobile !== '' && $assignedExecutive?->id) {
            $this->syncWhatsappGroup($mobile, (int) $assignedExecutive->id);
        }

        return $operationLead;
    }

    protected function syncWhatsappGroup(string $number, int $executiveId): void
    {
        try {
            $group = WhatsappMsgGroup::where('whatsapp_number', $number)->first();
            if ($group) {
                $ids = array_filter(explode(',', (string) $group->executive_ids));
                if (! in_array((string) $executiveId, $ids, true)) {
                    $ids[] = $executiveId;
                    $group->executive_ids = implode(',', $ids);
                    $group->save();
                }
            } else {
                WhatsappMsgGroup::create([
                    'whatsapp_number' => $number,
                    'executive_ids' => (string) $executiveId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('B2BCorporateLead: WhatsApp group sync failed', [
                'number' => $number,
                'executive' => $executiveId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function customerLabel(B2BUser $b2bUser): string
    {
        $company = trim((string) ($b2bUser->company_name ?? ''));
        if ($company !== '') {
            return $company;
        }

        return trim((string) ($b2bUser->name ?? 'B2B Corporate'));
    }

    protected function normalizeMobile(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        return strlen($digits) === 10 ? $digits : '';
    }

    public function buildLeadSource(B2BUser $b2bUser): string
    {
        $company = trim((string) ($b2bUser->company_name ?? $b2bUser->name ?? ''));

        return $company !== '' ? ('b2b corporate ('.$company.')') : 'b2b corporate';
    }
}
