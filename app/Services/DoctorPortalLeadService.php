<?php

namespace App\Services;

use App\Facades\UserAssignment;
use App\Models\DoctorPortalLead;
use App\Models\DoctorRequest;
use App\Models\Lead;
use App\Models\User;

class DoctorPortalLeadService
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

    public function createLead(DoctorRequest $doctor, array $input, string $source = 'manual'): DoctorPortalLead
    {
        $validated = $this->validateSingleInput($input);
        $mobile = $this->normalizeMobile((string) $validated['number']);
        $service = trim((string) ($validated['service'] ?? ''));
        $detail = trim((string) ($validated['detail'] ?? ''));
        $bulkQty = (int) $validated['bulk'];

        $salesLeadId = $this->createSalesLead($doctor, $mobile, $service, $detail, $bulkQty);

        return DoctorPortalLead::create([
            'doctor_request_id' => $doctor->id,
            'lead_id' => $salesLeadId,
            'mobile' => $mobile,
            'service' => $service !== '' ? $service : null,
            'detail' => $detail !== '' ? $detail : null,
            'bulk_qty' => $bulkQty,
            'commission_percent' => $doctor->portalLeadCommissionPercent(),
            'source' => $source,
        ]);
    }

    protected function createSalesLead(
        DoctorRequest $doctor,
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
            'status_remarks' => 'Doctor Portal · Bulk qty: '.$bulkQty.' · Commission: '.$doctor->portalLeadCommissionPercent().'%',
            'lead_source' => $this->buildLeadSource($doctor),
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

    public function buildLeadSource(DoctorRequest $doctor): string
    {
        $label = trim((string) ($doctor->name ?? $doctor->customer_name ?? ''));

        return $label !== '' ? ('doctor portal ('.$label.')') : 'doctor portal';
    }
}
