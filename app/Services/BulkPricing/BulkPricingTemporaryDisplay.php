<?php

namespace App\Services\BulkPricing;

use App\Models\BulkPricingRule;
use App\Models\BulkPricingRuleChange;
use App\Models\DoctorRequest;
use App\Models\Location;
use App\Services\JobRequestFreelancerServiceSync;
use App\Services\VendorServiceSync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Looks up a running temporary bulk price for portal screens (temporary price, normal price, period).
 */
class BulkPricingTemporaryDisplay
{
    private static array $ruleIds = [];

    private static array $changes = [];

    /**
     * @param  string  $kind  vendor | freelancer
     * @param  string  $field  price_12hr | price_24hr | price_onetime
     * @return array{temp: float, normal: ?float, start: mixed, end: mixed}|null
     */
    public static function provider(string $kind, Model $user, ?string $locationName, int $serviceId, int $subId, string $field, mixed $currentPrice): ?array
    {
        $ruleIds = self::activeTemporaryRuleIds([$kind]);
        if ($ruleIds === []) {
            return null;
        }

        $change = self::changes($ruleIds, $kind === 'vendor' ? 'vendor_price' : 'freelancer_price', (int) $user->getKey())
            ->first(fn ($c) => $c->field === $field
                && (int) ($c->target_key['service_id'] ?? 0) === $serviceId
                && (int) ($c->target_key['service_sub_service_id'] ?? 0) === $subId);

        if (! $change && $locationName) {
            $locationId = (int) (Location::query()->where('name', $locationName)->value('id') ?? 0);
            if ($locationId > 0) {
                $candidates = self::changes($ruleIds, 'location_service', $locationId)
                    ->filter(fn ($c) => $c->field === $field
                        && ($c->target_key['provider_type'] ?? '') === $kind
                        && (int) ($c->target_key['service_id'] ?? 0) === $serviceId);
                $change = $candidates->first(fn ($c) => (int) ($c->target_key['service_sub_service_id'] ?? 0) === $subId)
                    ?? $candidates->first(fn ($c) => (int) ($c->target_key['service_sub_service_id'] ?? 0) === 0);
                if ($change && ! self::same($currentPrice, $change->new_value)) {
                    $change = null;
                }
            }
        }

        if (! $change) {
            return null;
        }

        $normal = $change->old_value;
        if ($normal === null) {
            $defaults = $kind === 'vendor'
                ? VendorServiceSync::locationDefaults($locationName, $serviceId)
                : JobRequestFreelancerServiceSync::locationDefaults($locationName, $serviceId);
            $normal = $defaults[(string) $subId][$field] ?? $defaults['0'][$field] ?? null;
        }

        return self::result($change, $normal);
    }

    /**
     * @param  string  $which  doctor (payout) | website
     * @return array{temp: float, normal: ?float, start: mixed, end: mixed}|null
     */
    public static function doctor(DoctorRequest $doctor, string $which, int $serviceId, int $subId, string $mode): ?array
    {
        $isWebsite = $which === 'website';
        $ruleIds = self::activeTemporaryRuleIds([$isWebsite ? 'website_doctor' : 'doctor_payout']);
        if ($ruleIds === []) {
            return null;
        }
        $field = $isWebsite ? 'website_price' : 'doctor_price';

        $change = self::changes($ruleIds, 'doctor_pricing_json', (int) $doctor->id)
            ->first(fn ($c) => $c->field === $field
                && ($c->target_key['mode'] ?? '') === $mode
                && (int) ($c->target_key['sub_service_id'] ?? 0) === $subId
                && (! $serviceId || (int) ($c->target_key['service_id'] ?? 0) === $serviceId));

        if (! $change && $subId === 0) {
            $column = $isWebsite
                ? ['online' => 'website_customer_fee_online', 'home_visit' => 'website_customer_fee_home_visit', 'clinic_visit' => 'website_customer_fee_clinic'][$mode] ?? null
                : ['online' => 'online_charges', 'home_visit' => 'home_visit_charges', 'clinic_visit' => 'clinic_consultation_charges'][$mode] ?? null;
            $change = $column ? self::changes($ruleIds, 'doctor_column', (int) $doctor->id)->first(fn ($c) => $c->field === $column) : null;
        }

        if (! $change) {
            return null;
        }

        $normal = $change->old_value;
        if ($normal === null && is_array($doctor->consultation_pricing)) {
            foreach ($doctor->consultation_pricing as $row) {
                if (is_array($row) && (int) ($row['sub_service_id'] ?? 0) === $subId && isset($row['modes'][$mode]['original_'.$field])) {
                    $normal = $row['modes'][$mode]['original_'.$field];
                    break;
                }
            }
        }

        return self::result($change, $normal);
    }

    private static function result(BulkPricingRuleChange $change, mixed $normal): array
    {
        return [
            'temp' => (float) $change->new_value,
            'normal' => ($normal === null || $normal === '') ? null : (float) $normal,
            'start' => $change->rule?->time_period_start_date,
            'end' => $change->rule?->time_period_end_date,
        ];
    }

    private static function activeTemporaryRuleIds(array $pricingTypes): array
    {
        $cacheKey = implode(',', $pricingTypes);

        return self::$ruleIds[$cacheKey] ??= BulkPricingRule::query()
            ->where('state', BulkPricingRule::STATE_ACTIVE)
            ->where('time_period', 'temporary')
            ->whereIn('pricing_type', $pricingTypes)
            ->pluck('id')
            ->all();
    }

    private static function changes(array $ruleIds, string $targetType, int $targetId): Collection
    {
        $cacheKey = implode(',', $ruleIds)."|{$targetType}|{$targetId}";

        return self::$changes[$cacheKey] ??= BulkPricingRuleChange::query()
            ->whereIn('bulk_pricing_rule_id', $ruleIds)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->whereNull('reverted_at')
            ->with('rule')
            ->orderByDesc('id')
            ->get();
    }

    private static function same(mixed $a, mixed $b): bool
    {
        if ($a === null || $a === '' || $b === null || $b === '') {
            return false;
        }

        return abs((float) $a - (float) $b) < 0.005;
    }
}
