<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CorporateEmployee;
use App\Models\CorporateUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait ManagesCorporateEmployeeRecords
{
    protected function currentCorporate(): ?CorporateUser
    {
        return CorporateUser::find(session('corporate_user_id'));
    }

    protected function employeeQuery(CorporateUser $corporate)
    {
        return CorporateEmployee::query()->where('corporate_user_id', $corporate->id);
    }

    protected function findOwnedEmployee(CorporateUser $corporate, int $id): CorporateEmployee
    {
        return $this->employeeQuery($corporate)->whereKey($id)->firstOrFail();
    }

    protected function validateEmployeePayload(Request $request, CorporateUser $corporate, ?int $ignoreId = null, bool $requirePassword = true): array
    {
        $rules = [
            'employee_id' => [
                'required', 'string', 'max:100',
                Rule::unique('corporate_employees', 'employee_id')
                    ->where('corporate_user_id', $corporate->id)
                    ->ignore($ignoreId),
            ],
            'employee_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'email' => ['nullable', 'email', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'issuance_date' => ['nullable', 'date'],
            'last_working_date' => ['nullable', 'date'],
            'si_limit' => ['nullable', 'regex:/^\d+(\.\d+)?$/'],
            'active_from' => ['nullable', 'date'],
            'active_to' => ['nullable', 'date', 'after_or_equal:active_from'],
            'room_limit' => ['nullable', 'regex:/^\d+(\.\d+)?$/'],
            'policy_terms_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($ignoreId) {
            $rules['password'] = ['nullable', 'string', 'min:6', 'max:100'];
        } elseif ($requirePassword) {
            $rules['password'] = ['required', 'string', 'min:6', 'max:100'];
        }

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['policy_terms_file']);

        return $data;
    }

    protected function storePolicyFile(Request $request, ?CorporateEmployee $existing = null): ?string
    {
        if (! $request->hasFile('policy_terms_file')) {
            return $existing?->policy_terms_file;
        }

        if ($existing?->policy_terms_file) {
            \Storage::disk('public')->delete($existing->policy_terms_file);
        }

        return $request->file('policy_terms_file')->store('corporate-employees/policy-terms', 'public');
    }

    protected function employeeRowFromCsv(array $row, CorporateUser $corporate): ?array
    {
        $employeeId = trim((string) ($row['employee_id'] ?? ''));
        $employeeName = trim((string) ($row['employee_name'] ?? ''));
        if ($employeeId === '' || $employeeName === '') {
            return null;
        }

        $password = trim((string) ($row['password'] ?? ''));
        if ($password === '') {
            $password = 'Emp'.substr(md5($employeeId.$corporate->id), 0, 8);
        }

        return [
            'corporate_user_id' => $corporate->id,
            'employee_id' => $employeeId,
            'employee_name' => $employeeName,
            'date_of_birth' => $this->parseCsvDate($row['date_of_birth'] ?? null),
            'phone_number' => trim((string) ($row['phone_number'] ?? '')) ?: null,
            'gender' => $this->normalizeGender($row['gender'] ?? null),
            'email' => trim((string) ($row['email'] ?? '')) ?: null,
            'relationship' => trim((string) ($row['relationship'] ?? '')) ?: null,
            'issuance_date' => $this->parseCsvDate($row['issuance_date'] ?? null),
            'last_working_date' => $this->parseCsvDate($row['last_working_date'] ?? null),
            'si_limit' => $this->normalizeNumericField($row['si_limit'] ?? null),
            'active_from' => $this->parseCsvDate($row['active_from'] ?? null),
            'active_to' => $this->parseCsvDate($row['active_to'] ?? null),
            'room_limit' => $this->normalizeNumericField($row['room_limit'] ?? null),
            'password' => $password,
            'is_active' => true,
        ];
    }

    protected function parseCsvDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeGender(mixed $value): ?string
    {
        $v = strtolower(trim((string) $value));
        if ($v === '') {
            return null;
        }
        if (in_array($v, ['m', 'male'], true)) {
            return 'Male';
        }
        if (in_array($v, ['f', 'female'], true)) {
            return 'Female';
        }

        return 'Other';
    }

    protected function normalizeNumericField(mixed $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '' || ! preg_match('/^\d+(\.\d+)?$/', $v)) {
            return null;
        }

        return $v;
    }

    public static function bulkCsvHeaders(): array
    {
        return [
            'employee_id', 'employee_name', 'date_of_birth', 'phone_number', 'gender',
            'email', 'relationship', 'issuance_date', 'last_working_date', 'si_limit',
            'active_from', 'active_to', 'room_limit', 'password',
        ];
    }
}
