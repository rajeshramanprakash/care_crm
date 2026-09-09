<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\SalesReferralLead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesReferralLeadService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const SOURCE_SALES = 'sales';

    public const SOURCE_OPERATION = 'operation';

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

    /**
     * Staff generates a referral lead → auto-approved and assigned to a sales executive.
     * Manager approval is not required (for now).
     *
     * @param  'sales'|'operation'  $source
     */
    public function createLead(User $generator, array $input, string $source = self::SOURCE_SALES): SalesReferralLead
    {
        $source = $source === self::SOURCE_OPERATION ? self::SOURCE_OPERATION : self::SOURCE_SALES;
        $validated = $this->validateSingleInput($input);
        $mobile = $this->normalizeMobile((string) $validated['number']);
        $service = trim((string) ($validated['service'] ?? ''));
        $detail = trim((string) ($validated['detail'] ?? ''));
        $bulkQty = (int) $validated['bulk'];

        $managerId = (int) ($generator->parent_id ?? 0);
        $manager = $managerId > 0
            ? User::query()->where('id', $managerId)->where('is_active', 1)->first()
            : null;

        $excludeId = $source === self::SOURCE_OPERATION ? 0 : (int) $generator->id;
        $assignee = $this->pickSalesExecutive($excludeId);
        if (! $assignee) {
            throw ValidationException::withMessages([
                'number' => ['No sales executive is available to assign this lead.'],
            ]);
        }

        return DB::transaction(function () use ($generator, $source, $manager, $assignee, $mobile, $service, $detail, $bulkQty) {
            $referral = SalesReferralLead::create([
                'generated_by_user_id' => $generator->id,
                'source' => $source,
                'manager_user_id' => $manager?->id,
                'lead_id' => null,
                'assigned_executive_id' => null,
                'mobile' => $mobile,
                'service' => $service !== '' ? $service : null,
                'detail' => $detail !== '' ? $detail : null,
                'bulk_qty' => $bulkQty,
                'commission_percent' => $generator->referralLeadCommissionPercent(),
                'status' => self::STATUS_PENDING,
            ]);

            return $this->assignToSalesAndApprove($referral, $assignee, $generator, null, true);
        });
    }

    public function approve(SalesReferralLead $referral, User $manager): SalesReferralLead
    {
        if ($referral->status !== self::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => ['This referral lead is already '.$referral->status.'.'],
            ]);
        }

        $this->assertManagerOwnsReferral($referral, $manager);

        $generatorId = (int) $referral->generated_by_user_id;
        $excludeId = ($referral->source ?? self::SOURCE_SALES) === self::SOURCE_OPERATION
            ? 0
            : $generatorId;

        $assignee = $this->pickSalesExecutive($excludeId);
        if (! $assignee) {
            throw ValidationException::withMessages([
                'status' => ['No sales executive is available to assign this lead.'],
            ]);
        }

        $generator = User::find($generatorId);

        return DB::transaction(function () use ($referral, $manager, $assignee, $generator) {
            return $this->assignToSalesAndApprove($referral, $assignee, $generator, $manager, false);
        });
    }

    public function reject(SalesReferralLead $referral, User $manager, ?string $remark = null): SalesReferralLead
    {
        if ($referral->status !== self::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => ['This referral lead is already '.$referral->status.'.'],
            ]);
        }

        $this->assertManagerOwnsReferral($referral, $manager);

        $referral->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by_user_id' => $manager->id,
            'reviewed_at' => now(),
            'manager_remark' => $remark !== null && trim($remark) !== '' ? trim($remark) : null,
        ]);

        return $referral->fresh(['generatedBy']);
    }

    /**
     * Create CRM sales lead + mark referral approved.
     */
    protected function assignToSalesAndApprove(
        SalesReferralLead $referral,
        User $assignee,
        ?User $generator,
        ?User $reviewer,
        bool $autoApproved
    ): SalesReferralLead {
        $sourceLabel = ($referral->source ?? self::SOURCE_SALES) === self::SOURCE_OPERATION
            ? 'Operation Referral'
            : 'Sales Referral';
        $sourceKey = ($referral->source ?? self::SOURCE_SALES) === self::SOURCE_OPERATION
            ? 'operation referral'
            : 'sales referral';

        $generatorLabel = $generator
            ? $this->userLabel($generator)
            : ('User #'.$referral->generated_by_user_id);

        $approvalNote = $autoApproved
            ? 'Auto-approved'
            : ('Approved by '.$this->userLabel($reviewer));

        $lead = Lead::create([
            'date' => now(),
            'executive' => $assignee->id,
            'customer_name' => $referral->mobile,
            'contact_no' => $referral->mobile,
            'query' => $referral->service,
            'query_remarks' => $referral->detail,
            'status_remarks' => $sourceLabel.' · '.$approvalNote
                .' · Generated by '.$generatorLabel
                .' · Assigned to '.$this->userLabel($assignee)
                .' · Bulk qty: '.$referral->bulk_qty,
            'lead_source' => $sourceKey.' ('.$generatorLabel.')',
            'contact_type' => 'call',
            'status' => 'follow-up',
            'stage' => 'active',
        ]);

        $referral->update([
            'status' => self::STATUS_APPROVED,
            'lead_id' => $lead->id,
            'assigned_executive_id' => $assignee->id,
            'reviewed_by_user_id' => $reviewer?->id,
            'reviewed_at' => now(),
            'manager_remark' => $autoApproved ? 'Auto-approved' : null,
        ]);

        return $referral->fresh(['generatedBy', 'assignedExecutive', 'lead']);
    }

    protected function assertManagerOwnsReferral(SalesReferralLead $referral, User $manager): void
    {
        $teamIds = User::query()
            ->where('parent_id', $manager->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $owns = ((int) $referral->manager_user_id === (int) $manager->id)
            || in_array((int) $referral->generated_by_user_id, $teamIds, true);

        if (! $owns) {
            throw ValidationException::withMessages([
                'status' => ['You are not allowed to review this referral lead.'],
            ]);
        }
    }

    /**
     * Round-robin pick among active Sales users.
     * Pass $excludeUserId > 0 to skip a specific user (e.g. sales generator).
     */
    protected function pickSalesExecutive(int $excludeUserId = 0): ?User
    {
        $base = User::query()
            ->whereRaw('FIND_IN_SET(role_id, "2")')
            ->where('is_active', 1);

        if ($excludeUserId > 0) {
            $base->where('id', '!=', $excludeUserId);
        }

        $next = (clone $base)->where('is_next', 1)->orderBy('id')->first();
        if (! $next) {
            $next = (clone $base)->orderBy('id')->first();
        }

        if (! $next) {
            return null;
        }

        $clear = User::query()
            ->whereRaw('FIND_IN_SET(role_id, "2")')
            ->where('is_active', 1)
            ->where('id', '!=', $next->id);

        if ($excludeUserId > 0) {
            $clear->where('id', '!=', $excludeUserId);
        }
        $clear->update(['is_next' => 0]);

        $next->is_next = 0;
        $next->save();

        $followingQuery = User::query()
            ->whereRaw('FIND_IN_SET(role_id, "2")')
            ->where('is_active', 1)
            ->where('id', '>', $next->id);

        if ($excludeUserId > 0) {
            $followingQuery->where('id', '!=', $excludeUserId);
        }

        $following = $followingQuery->orderBy('id')->first();

        if (! $following) {
            $wrap = User::query()
                ->whereRaw('FIND_IN_SET(role_id, "2")')
                ->where('is_active', 1);

            if ($excludeUserId > 0) {
                $wrap->where('id', '!=', $excludeUserId);
            }

            $following = $wrap->orderBy('id')->first();
        }

        if ($following) {
            $following->is_next = 1;
            $following->save();
        }

        return $next;
    }

    protected function normalizeMobile(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        return strlen($digits) === 10 ? $digits : '';
    }

    protected function userLabel(User $user): string
    {
        $name = trim(($user->f_name ?? '').' '.($user->l_name ?? ''));

        return $name !== '' ? $name : ('User #'.$user->id);
    }
}
