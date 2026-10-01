<?php

namespace App\Services\BulkPricing;

use App\Models\BulkPricingRule;
use App\Models\BulkPricingRuleChange;
use App\Models\DoctorConsultationService;
use App\Models\DoctorConsultationServiceSubService;
use App\Models\DoctorRequest;
use App\Models\DoctorRequestPriceLog;
use App\Models\JobRequest;
use App\Models\JobRequestServicePrice;
use App\Models\Location;
use App\Models\LocationDoctorConsultationPrice;
use App\Models\Service;
use App\Models\ServiceSubService;
use App\Models\Vendor;
use App\Models\VendorServicePrice;
use App\Services\JobRequestFreelancerServiceSync;
use App\Services\VendorServiceSync;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Applies / reverts bulk pricing rules.
 *
 * The location price is the base: target = location price ± value.
 * Increase → every price below the target is raised to the target, higher prices stay.
 * Decrease → every price above the target is lowered to the target, lower prices stay.
 */
class BulkPricingRuleApplier
{
    /** Rules created before this date have no change log and are reverted from the original_* columns. */
    private const CHANGE_LOG_SINCE = '2026-10-01 00:00:00';

    private const PROVIDER_FIELDS = ['price_12hr', 'price_24hr', 'price_onetime'];

    private const DOCTOR_MODES = ['online', 'home_visit', 'clinic_visit'];

    private const PAYOUT_COLUMNS = [
        'online' => 'online_charges',
        'home_visit' => 'home_visit_charges',
        'clinic_visit' => 'clinic_consultation_charges',
    ];

    private const WEBSITE_FEE_COLUMNS = [
        'online' => 'website_customer_fee_online',
        'home_visit' => 'website_customer_fee_home_visit',
        'clinic_visit' => 'website_customer_fee_clinic',
    ];

    private const FIELD_LABELS = [
        'price_12hr' => '12 Hours',
        'price_24hr' => '24 Hours',
        'price_onetime' => 'One-time',
        'online' => 'Online',
        'home_visit' => 'Home Visit',
        'clinic_visit' => 'Clinic',
    ];

    private BulkPricingRule $rule;

    private int $affected = 0;

    /** @var array<int, int>|null location id set, null = all cities */
    private ?array $citySet = null;

    /** @var array<string, float|null> location value before this rule changed it, keyed "key|field" */
    private array $locationOldValues = [];

    /** @var array<string, object> location_services rows keyed "loc|service|sub" */
    private array $providerBaseRows = [];

    /** @var array<string, LocationDoctorConsultationPrice> keyed "loc|service|sub|mode" */
    private array $doctorBaseRows = [];

    private array $locationCache = [];

    private array $nameCache = [];

    // ─── Public API ──────────────────────────────────────────────────────

    public function apply(BulkPricingRule $rule): int
    {
        $this->boot($rule);

        DB::transaction(function () {
            match ($this->rule->pricing_type) {
                'vendor' => $this->applyProviders('vendor'),
                'freelancer' => $this->applyProviders('freelancer'),
                'website_general' => $this->updateLocationServiceRows('website'),
                'doctor_payout', 'website_doctor' => $this->applyDoctors(),
                default => null,
            };
        });

        return $this->affected;
    }

    public function revert(BulkPricingRule $rule): int
    {
        $this->boot($rule);
        $reverted = 0;

        $changes = $rule->changes()->whereNull('reverted_at')->orderByDesc('id')->get();

        if ($changes->isEmpty() && $rule->created_at && $rule->created_at->lt(self::CHANGE_LOG_SINCE) && ! $rule->changes()->exists()) {
            return $this->revertLegacy($rule);
        }

        DB::transaction(function () use ($changes, &$reverted) {
            foreach ($changes as $change) {
                try {
                    if ($this->revertChange($change)) {
                        $reverted++;
                    }
                } catch (\Throwable $e) {
                    Log::warning("Bulk pricing revert failed for change #{$change->id}: ".$e->getMessage());
                }
                $change->update(['reverted_at' => now()]);
            }
        });

        return $reverted;
    }

    /**
     * Apply active "All + New" / "New only" rules to a provider that just registered.
     */
    public static function applyToNewRegistration(Model $model): void
    {
        $kind = match (true) {
            $model instanceof Vendor => 'vendor',
            $model instanceof JobRequest => 'freelancer',
            $model instanceof DoctorRequest => 'doctor',
            default => null,
        };
        if ($kind === null) {
            return;
        }

        $types = $kind === 'doctor' ? ['doctor_payout', 'website_doctor'] : [$kind];
        $registeredAt = $model->created_at ?? now();

        $rules = BulkPricingRule::query()
            ->where('state', BulkPricingRule::STATE_ACTIVE)
            ->whereIn('pricing_type', $types)
            ->whereIn('apply_to', ['all', 'new'])
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            $adoptEqual = false;
            if ($rule->apply_to === 'all') {
                // Vendor / freelancer prices resolve live from the (already changed) location price.
                // Doctors copy the location price at registration; a temporary rule must still revert it later.
                if ($kind !== 'doctor' || ! $rule->isTemporary()) {
                    continue;
                }
                $adoptEqual = true;
            } elseif (! self::registeredWithinRange($rule, $registeredAt)) {
                continue;
            }

            try {
                $applier = new self();
                $applier->boot($rule);
                DB::transaction(function () use ($applier, $kind, $model, $adoptEqual) {
                    match ($kind) {
                        'vendor' => $applier->processVendor($model->fresh(), false),
                        'freelancer' => $applier->processFreelancer($model->fresh(), false),
                        'doctor' => $applier->processDoctor($model->fresh(), $adoptEqual),
                    };
                });
                if ($applier->affected > 0) {
                    $rule->increment('affected_count', $applier->affected);
                }
            } catch (\Throwable $e) {
                Log::error("Bulk pricing rule #{$rule->id} failed for new {$kind} #{$model->getKey()}: ".$e->getMessage());
            }
        }
    }

    public static function targetPrice(BulkPricingRule $rule, mixed $base): ?float
    {
        if ($base === null || $base === '' || ! is_numeric($base)) {
            return null;
        }
        $base = (float) $base;
        $value = (float) $rule->value;

        $target = match ($rule->change_type) {
            'increase_fixed' => $base + $value,
            'increase_percent' => $base + ($base * $value / 100),
            'decrease_fixed' => $base - $value,
            'decrease_percent' => $base - ($base * $value / 100),
            default => $base,
        };

        return round(max(0, $target), 2);
    }

    /**
     * @return list<int>|null null = all cities
     */
    public static function cityIdsFor(BulkPricingRule $rule): ?array
    {
        $filter = (string) $rule->city_filter;
        if (! in_array($filter, ['tier_1', 'tier_2', 'tier_3'], true)) {
            return null;
        }
        if (! empty($rule->selected_cities)) {
            return array_values(array_map('intval', $rule->selected_cities));
        }
        $tierName = 'Tier '.substr($filter, -1);

        return Location::query()->where('tier', $tierName)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public static function registeredWithinRange(BulkPricingRule $rule, $registeredAt): bool
    {
        if ($rule->apply_from_date && $registeredAt < $rule->apply_from_date) {
            return false;
        }
        if ($rule->apply_to_date && $registeredAt > $rule->apply_to_date) {
            return false;
        }

        return true;
    }

    // ─── Setup / helpers ─────────────────────────────────────────────────

    private function boot(BulkPricingRule $rule): void
    {
        // Some servers set serialize_precision high, which writes 6.98 into the pricing JSON as 6.9800000000000004263...
        ini_set('serialize_precision', '-1');

        $this->rule = $rule;
        $this->affected = 0;
        $cityIds = self::cityIdsFor($rule);
        $this->citySet = $cityIds === null ? null : array_flip($cityIds);

        $this->locationOldValues = [];
        $logged = BulkPricingRuleChange::query()
            ->where('bulk_pricing_rule_id', $rule->id)
            ->whereIn('target_type', ['location_service', 'location_doctor_price'])
            ->whereNull('reverted_at')
            ->get();
        foreach ($logged as $change) {
            $this->locationOldValues[$this->keyString($change->target_key).'|'.$change->field] =
                $change->old_value !== null ? (float) $change->old_value : null;
        }
    }

    private function keyString(array $key): string
    {
        return implode('|', array_map(fn ($v) => (string) ($v ?? 0), array_values($key)));
    }

    private function cityAllowed(?int $locationId): bool
    {
        if ($this->citySet === null) {
            return true;
        }

        return $locationId !== null && isset($this->citySet[$locationId]);
    }

    private function serviceMatches(int $serviceId, int $subId): bool
    {
        if ($this->rule->service_id && (int) $this->rule->service_id !== $serviceId) {
            return false;
        }
        if ($this->rule->sub_service_id && (int) $this->rule->sub_service_id !== $subId) {
            return false;
        }

        return true;
    }

    private function needsChange(?float $current, float $target): bool
    {
        if ($current === null) {
            return false;
        }

        return $this->rule->isIncrease()
            ? $current < $target - 0.001
            : $current > $target + 0.001;
    }

    private static function num(mixed $value): ?float
    {
        return ($value === null || $value === '' || ! is_numeric($value)) ? null : (float) $value;
    }

    private static function same(mixed $a, mixed $b): bool
    {
        $a = self::num($a);
        $b = self::num($b);
        if ($a === null || $b === null) {
            return $a === $b;
        }

        return abs($a - $b) < 0.005;
    }

    private function providerFields(): array
    {
        return match ($this->rule->normalizedMode()) {
            '12_hours' => ['price_12hr'],
            '24_hours' => ['price_24hr'],
            'both' => ['price_12hr', 'price_24hr'],
            'one_time' => ['price_onetime'],
            default => self::PROVIDER_FIELDS,
        };
    }

    private function doctorModes(): array
    {
        $mode = $this->rule->normalizedMode();

        return in_array($mode, self::DOCTOR_MODES, true) ? [$mode] : self::DOCTOR_MODES;
    }

    private function locationByNames(array $names): ?Location
    {
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            if (! array_key_exists($name, $this->locationCache)) {
                $this->locationCache[$name] = Location::query()->where('name', $name)->first();
            }
            if ($this->locationCache[$name]) {
                return $this->locationCache[$name];
            }
        }

        return null;
    }

    private function name(string $type, ?int $id): string
    {
        if (! $id) {
            return '';
        }
        $cacheKey = $type.':'.$id;
        if (! array_key_exists($cacheKey, $this->nameCache)) {
            $this->nameCache[$cacheKey] = (string) (match ($type) {
                'location' => Location::query()->whereKey($id)->value('name'),
                'service' => Service::query()->whereKey($id)->value('name'),
                'sub' => ServiceSubService::query()->whereKey($id)->value('name'),
                'doctor_service' => DoctorConsultationService::query()->whereKey($id)->value('name'),
                'doctor_sub' => DoctorConsultationServiceSubService::query()->whereKey($id)->value('name'),
                default => '',
            } ?? '');
        }

        return $this->nameCache[$cacheKey];
    }

    private function serviceText(int $serviceId, int $subId, bool $doctor = false): string
    {
        $svc = $this->name($doctor ? 'doctor_service' : 'service', $serviceId) ?: ('Service #'.$serviceId);
        $sub = $subId > 0 ? $this->name($doctor ? 'doctor_sub' : 'sub', $subId) : '';

        return $sub !== '' ? "{$svc} › {$sub}" : $svc;
    }

    private function log(string $targetType, ?int $targetId, array $key, string $field, mixed $old, mixed $new, string $label): void
    {
        BulkPricingRuleChange::create([
            'bulk_pricing_rule_id' => $this->rule->id,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_key' => $key,
            'field' => $field,
            'label' => mb_substr($label, 0, 255),
            'old_value' => self::num($old),
            'new_value' => self::num($new),
        ]);
        $this->affected++;
    }

    /**
     * Location value before this rule touched it (equals the current value when the rule did not change it).
     */
    private function baseValue(string $key, string $field, mixed $currentValue): ?float
    {
        $k = $key.'|'.$field;

        return array_key_exists($k, $this->locationOldValues) ? $this->locationOldValues[$k] : self::num($currentValue);
    }

    private function userQuery(Builder $query): Builder
    {
        if (in_array($this->rule->apply_to, ['old', 'new'], true)) {
            if ($this->rule->apply_from_date) {
                $query->where('created_at', '>=', $this->rule->apply_from_date);
            }
            if ($this->rule->apply_to_date) {
                $query->where('created_at', '<=', $this->rule->apply_to_date);
            }
        }

        return $query;
    }

    // ─── Vendor / Freelancer / Website general (location_services) ───────

    private function loadProviderBaseRows(string $providerType): void
    {
        $this->providerBaseRows = [];
        DB::table('location_services')
            ->where('provider_type', $providerType)
            ->when($this->rule->service_id, fn ($q) => $q->where('service_id', $this->rule->service_id))
            ->orderBy('id')
            ->get()
            ->each(function ($row) {
                $this->providerBaseRows[$row->location_id.'|'.$row->service_id.'|'.(int) $row->service_sub_service_id] = $row;
            });
    }

    private function providerBaseRow(int $locationId, int $serviceId, int $subId): ?object
    {
        return $this->providerBaseRows["{$locationId}|{$serviceId}|{$subId}"]
            ?? $this->providerBaseRows["{$locationId}|{$serviceId}|0"]
            ?? null;
    }

    private function applyProviders(string $providerType): void
    {
        $this->loadProviderBaseRows($providerType);
        // "All" changes the location default (users without their own price follow it automatically),
        // so only users with an explicit price need a check. "Old"/"New" leave the location untouched.
        $explicitOnly = $this->rule->apply_to === 'all';

        if ($providerType === 'vendor') {
            $this->userQuery(Vendor::query())->orderBy('id')->chunk(100, function ($vendors) use ($explicitOnly) {
                foreach ($vendors as $vendor) {
                    $this->processVendor($vendor, $explicitOnly);
                }
            });
        } else {
            $this->userQuery(JobRequest::query())->orderBy('id')->chunk(200, function ($rows) use ($explicitOnly) {
                foreach ($rows as $jobRequest) {
                    $this->processFreelancer($jobRequest, $explicitOnly);
                }
            });
        }

        if ($this->rule->apply_to === 'all') {
            $this->updateLocationServiceRows($providerType);
        }
    }

    private function updateLocationServiceRows(string $providerType): void
    {
        $rows = DB::table('location_services')
            ->where('provider_type', $providerType)
            ->when($this->rule->service_id, fn ($q) => $q->where('service_id', $this->rule->service_id))
            ->when($this->rule->sub_service_id, fn ($q) => $q->where('service_sub_service_id', $this->rule->sub_service_id))
            ->when($this->citySet !== null, fn ($q) => $q->whereIn('location_id', array_keys($this->citySet)))
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $subId = (int) $row->service_sub_service_id;
            $key = [
                'location_id' => (int) $row->location_id,
                'service_id' => (int) $row->service_id,
                'service_sub_service_id' => $subId,
                'provider_type' => $providerType,
            ];
            $update = [];
            foreach ($this->providerFields() as $field) {
                $old = self::num($row->{$field});
                $new = self::targetPrice($this->rule, $old);
                if ($old === null || $new === null || self::same($old, $new)) {
                    continue;
                }
                $update[$field] = $new;
                if ($this->rule->isTemporary() && $row->{'original_'.$field} === null) {
                    $update['original_'.$field] = $old;
                }
                $this->log('location_service', (int) $row->location_id, $key, $field, $old, $new, sprintf(
                    'Location %s · %s pricing · %s · %s',
                    $this->name('location', (int) $row->location_id),
                    ucfirst($providerType),
                    $this->serviceText((int) $row->service_id, $subId),
                    self::FIELD_LABELS[$field]
                ));
            }
            if ($update !== []) {
                DB::table('location_services')->where('id', $row->id)->update($update + ['updated_at' => now()]);
            }
        }
    }

    /**
     * @return list<array{0: int, 1: int}>
     */
    private function vendorServiceKeys(Vendor $vendor): array
    {
        $keys = [];
        foreach (VendorServiceSync::blocksForVendor($vendor) as $block) {
            $serviceId = (int) ($block['service_id'] ?? 0);
            if ($serviceId <= 0) {
                continue;
            }
            $keys["{$serviceId}|0"] = [$serviceId, 0];
            foreach ($block['sub_services'] ?? [] as $sub) {
                $subId = (int) ($sub['sub_service_id'] ?? $sub['id'] ?? 0);
                if ($subId > 0) {
                    $keys["{$serviceId}|{$subId}"] = [$serviceId, $subId];
                }
            }
        }
        VendorServicePrice::query()->where('vendor_id', $vendor->id)->get(['service_id', 'service_sub_service_id'])
            ->each(function ($row) use (&$keys) {
                $keys[(int) $row->service_id.'|'.(int) $row->service_sub_service_id] = [(int) $row->service_id, (int) $row->service_sub_service_id];
            });

        return array_values($keys);
    }

    private function processVendor(?Vendor $vendor, bool $explicitOnly): void
    {
        if (! $vendor) {
            return;
        }
        if ($this->providerBaseRows === []) {
            $this->loadProviderBaseRows('vendor');
        }
        $location = $this->locationByNames([$vendor->location]);
        if (! $location || ! $this->cityAllowed((int) $location->id)) {
            return;
        }

        foreach ($this->vendorServiceKeys($vendor) as [$serviceId, $subId]) {
            if (! $this->serviceMatches($serviceId, $subId)) {
                continue;
            }
            $base = $this->providerBaseRow((int) $location->id, $serviceId, $subId);
            if (! $base) {
                continue;
            }
            $baseKey = $base->location_id.'|'.$base->service_id.'|'.(int) $base->service_sub_service_id.'|vendor';

            $row = VendorServicePrice::query()
                ->where('vendor_id', $vendor->id)
                ->where('service_id', $serviceId)
                ->where('service_sub_service_id', $subId)
                ->first();
            $json = $row ? null : VendorServiceSync::jsonBlockOverride($vendor, $serviceId, $subId);

            $pending = $this->pendingProviderChanges($row, $json, $base, $baseKey, $explicitOnly);
            if ($pending === []) {
                continue;
            }

            if (! $row) {
                $row = new VendorServicePrice([
                    'vendor_id' => $vendor->id,
                    'service_id' => $serviceId,
                    'service_sub_service_id' => $subId,
                    'price_12hr' => self::num($json['price_12hr'] ?? null),
                    'price_24hr' => self::num($json['price_24hr'] ?? null),
                    'price_onetime' => self::num($json['price_onetime'] ?? null),
                ]);
            }

            $this->writeProviderRow($row, $pending, 'vendor_price', (int) $vendor->id, [
                'vendor_id' => (int) $vendor->id,
                'service_id' => $serviceId,
                'service_sub_service_id' => $subId,
            ], sprintf('Vendor #%d %s · %s · %s', $vendor->id, $vendor->name, $location->name, $this->serviceText($serviceId, $subId)));
        }
    }

    /**
     * @return list<array{0: int, 1: int}>
     */
    private function freelancerServiceKeys(JobRequest $jobRequest): array
    {
        $keys = [];
        $serviceId = (int) (Service::query()->where('name', $jobRequest->job_title)->value('id') ?? 0);
        if ($serviceId > 0) {
            $keys["{$serviceId}|0"] = [$serviceId, 0];
            foreach (JobRequestFreelancerServiceSync::decodeJsonArray($jobRequest->service_sub_services) as $sub) {
                $subId = is_array($sub) ? (int) ($sub['sub_service_id'] ?? $sub['id'] ?? 0) : 0;
                if ($subId > 0) {
                    $keys["{$serviceId}|{$subId}"] = [$serviceId, $subId];
                }
            }
        }
        JobRequestServicePrice::query()->where('job_request_id', $jobRequest->id)->get(['service_id', 'service_sub_service_id'])
            ->each(function ($row) use (&$keys) {
                $keys[(int) $row->service_id.'|'.(int) $row->service_sub_service_id] = [(int) $row->service_id, (int) $row->service_sub_service_id];
            });

        return array_values($keys);
    }

    private function processFreelancer(?JobRequest $jobRequest, bool $explicitOnly): void
    {
        if (! $jobRequest) {
            return;
        }
        if ($this->providerBaseRows === []) {
            $this->loadProviderBaseRows('freelancer');
        }
        $location = $this->locationByNames([$jobRequest->location, $jobRequest->city]);
        if (! $location || ! $this->cityAllowed((int) $location->id)) {
            return;
        }

        foreach ($this->freelancerServiceKeys($jobRequest) as [$serviceId, $subId]) {
            if (! $this->serviceMatches($serviceId, $subId)) {
                continue;
            }
            $base = $this->providerBaseRow((int) $location->id, $serviceId, $subId);
            if (! $base) {
                continue;
            }
            $baseKey = $base->location_id.'|'.$base->service_id.'|'.(int) $base->service_sub_service_id.'|freelancer';

            $row = JobRequestServicePrice::query()
                ->where('job_request_id', $jobRequest->id)
                ->where('service_id', $serviceId)
                ->where('service_sub_service_id', $subId)
                ->first();

            $pending = $this->pendingProviderChanges($row, null, $base, $baseKey, $explicitOnly);
            if ($pending === []) {
                continue;
            }

            $row ??= new JobRequestServicePrice([
                'job_request_id' => $jobRequest->id,
                'service_id' => $serviceId,
                'service_sub_service_id' => $subId,
            ]);

            $this->writeProviderRow($row, $pending, 'freelancer_price', (int) $jobRequest->id, [
                'job_request_id' => (int) $jobRequest->id,
                'service_id' => $serviceId,
                'service_sub_service_id' => $subId,
            ], sprintf('Freelancer #%d %s · %s · %s', $jobRequest->id, $jobRequest->name, $location->name, $this->serviceText($serviceId, $subId)));
        }
    }

    /**
     * @return array<string, array{old: ?float, new: float, current: float}>
     */
    private function pendingProviderChanges(?Model $row, ?array $json, object $base, string $baseKey, bool $explicitOnly): array
    {
        $pending = [];
        foreach ($this->providerFields() as $field) {
            $baseValue = $this->baseValue($baseKey, $field, $base->{$field} ?? null);
            $target = self::targetPrice($this->rule, $baseValue);
            if ($target === null) {
                continue;
            }
            $explicit = self::num($row ? $row->{$field} : ($json[$field] ?? null));
            if ($explicitOnly && $explicit === null) {
                continue;
            }
            $current = $explicit ?? $baseValue;
            if ($this->needsChange($current, $target)) {
                $pending[$field] = ['old' => $explicit, 'new' => $target, 'current' => $current];
            }
        }

        return $pending;
    }

    private function writeProviderRow(Model $row, array $pending, string $targetType, int $targetId, array $key, string $label): void
    {
        foreach ($pending as $field => $change) {
            if ($this->rule->isTemporary() && $row->{'original_'.$field} === null) {
                $row->{'original_'.$field} = $change['current'];
            }
            $row->{$field} = $change['new'];
            $this->log($targetType, $targetId, $key, $field, $change['old'], $change['new'], $label.' · '.self::FIELD_LABELS[$field]);
        }
        $row->save();
    }

    // ─── Doctors (payout + website consultation) ─────────────────────────

    private function isPayout(): bool
    {
        return $this->rule->pricing_type === 'doctor_payout';
    }

    private function loadDoctorBaseRows(): void
    {
        $this->doctorBaseRows = [];
        LocationDoctorConsultationPrice::query()
            ->when($this->rule->service_id, fn ($q) => $q->where('doctor_consultation_service_id', $this->rule->service_id))
            ->get()
            ->each(function (LocationDoctorConsultationPrice $row) {
                $sub = (int) ($row->doctor_consultation_service_sub_service_id ?? 0);
                $this->doctorBaseRows[$row->location_id.'|'.$row->doctor_consultation_service_id.'|'.$sub.'|'.$row->consultation_mode] = $row;
            });
    }

    private function doctorBaseValue(int $locationId, int $serviceId, int $subId, string $mode): ?float
    {
        $field = $this->isPayout() ? 'doctor_max_price' : 'website_price';
        foreach (array_unique([$subId, 0]) as $sid) {
            $row = $this->doctorBaseRows["{$locationId}|{$serviceId}|{$sid}|{$mode}"] ?? null;
            if ($row) {
                return $this->baseValue("{$locationId}|{$serviceId}|{$sid}|{$mode}", $field, $row->{$field});
            }
        }

        return null;
    }

    private function applyDoctors(): void
    {
        $this->loadDoctorBaseRows();

        $this->userQuery(DoctorRequest::query())->orderBy('id')->chunk(100, function ($doctors) {
            foreach ($doctors as $doctor) {
                $this->processDoctor($doctor, false);
            }
        });

        if ($this->rule->apply_to === 'all') {
            $this->updateLocationDoctorRows();
        }
    }

    private function updateLocationDoctorRows(): void
    {
        $field = $this->isPayout() ? 'doctor_max_price' : 'website_price';

        $rows = LocationDoctorConsultationPrice::query()
            ->when($this->rule->service_id, fn ($q) => $q->where('doctor_consultation_service_id', $this->rule->service_id))
            ->when($this->rule->sub_service_id, fn ($q) => $q->where('doctor_consultation_service_sub_service_id', $this->rule->sub_service_id))
            ->whereIn('consultation_mode', $this->doctorModes())
            ->when($this->citySet !== null, fn ($q) => $q->whereIn('location_id', array_keys($this->citySet)))
            ->get();

        foreach ($rows as $row) {
            $old = self::num($row->{$field});
            $new = self::targetPrice($this->rule, $old);
            if ($old === null || $new === null || self::same($old, $new)) {
                continue;
            }
            if ($this->rule->isTemporary() && $row->{'original_'.$field} === null) {
                $row->{'original_'.$field} = $old;
            }
            $row->{$field} = $new;
            $row->save();

            $sub = (int) ($row->doctor_consultation_service_sub_service_id ?? 0);
            $this->log('location_doctor_price', (int) $row->location_id, [
                'location_id' => (int) $row->location_id,
                'service_id' => (int) $row->doctor_consultation_service_id,
                'sub_service_id' => $sub,
                'mode' => (string) $row->consultation_mode,
            ], $field, $old, $new, sprintf(
                'Location %s · Doctor %s · %s · %s',
                $this->name('location', (int) $row->location_id),
                $this->isPayout() ? 'max payout' : 'website price',
                $this->serviceText((int) $row->doctor_consultation_service_id, $sub, true),
                self::FIELD_LABELS[$row->consultation_mode] ?? $row->consultation_mode
            ));
        }
    }

    /**
     * @param  bool  $adoptEqual  new registration under a temporary "All" rule: a price already equal to the
     *                            temporary location price is logged so it reverts with the rule.
     */
    private function processDoctor(?DoctorRequest $doctor, bool $adoptEqual): void
    {
        if (! $doctor) {
            return;
        }
        if ($this->doctorBaseRows === []) {
            $this->loadDoctorBaseRows();
        }

        $isPayout = $this->isPayout();
        $jsonField = $isPayout ? 'doctor_price' : 'website_price';
        $modes = $this->doctorModes();
        $fallbackLocation = $this->locationByNames([$doctor->city, $doctor->location]);
        $titleServiceId = (int) (DoctorConsultationService::query()->where('name', $doctor->job_title)->value('id') ?? 0);
        $who = sprintf('Doctor #%d %s', $doctor->id, $doctor->name);

        $pricing = $doctor->consultation_pricing;
        $jsonChanged = false;
        $hasJsonForService = false;
        $serviceLevelTargets = [];
        $priceLogs = [];

        if (is_array($pricing)) {
            foreach ($pricing as $i => $row) {
                if (! is_array($row) || ! is_array($row['modes'] ?? null)) {
                    continue;
                }
                $serviceId = (int) ($row['service_id'] ?? 0) ?: $titleServiceId;
                $subId = (int) ($row['sub_service_id'] ?? 0);
                if (! $this->serviceMatches($serviceId, $subId)) {
                    continue;
                }
                $locationId = (int) ($row['location_id'] ?? 0) ?: (int) ($fallbackLocation->id ?? 0);
                if ($locationId <= 0 || ! $this->cityAllowed($locationId)) {
                    continue;
                }
                $hasJsonForService = true;

                foreach ($modes as $mode) {
                    $modeRow = $row['modes'][$mode] ?? null;
                    if (! is_array($modeRow)) {
                        continue;
                    }
                    $target = self::targetPrice($this->rule, $this->doctorBaseValue($locationId, $serviceId, $subId, $mode));
                    if ($target === null) {
                        continue;
                    }
                    $explicit = self::num($modeRow[$jsonField] ?? null);
                    $current = $explicit ?? ($isPayout ? null : self::num($modeRow['doctor_price'] ?? null));
                    if ($current === null) {
                        continue;
                    }

                    $key = ['service_id' => $serviceId, 'sub_service_id' => $subId, 'mode' => $mode, 'location_id' => $locationId];
                    $label = sprintf('%s · %s · %s', $who, $this->serviceText($serviceId, $subId, true), self::FIELD_LABELS[$mode]);

                    if ($this->needsChange($current, $target)) {
                        if ($this->rule->isTemporary() && ! isset($modeRow['original_'.$jsonField])) {
                            $pricing[$i]['modes'][$mode]['original_'.$jsonField] = $current;
                        }
                        $pricing[$i]['modes'][$mode][$jsonField] = $target;
                        $this->log('doctor_pricing_json', (int) $doctor->id, $key, $jsonField, $explicit, $target, $label);
                        $priceLogs[] = [$mode.($subId > 0 ? ' ('.($row['sub_service_name'] ?? 'Sub-service').')' : ''), $current, $target];
                        $jsonChanged = true;

                        if ($isPayout) {
                            $max = self::num($modeRow['doctor_max_price'] ?? null);
                            if ($max !== null && $this->needsChange($max, $target)) {
                                $pricing[$i]['modes'][$mode]['doctor_max_price'] = $target;
                                $this->log('doctor_pricing_json', (int) $doctor->id, $key, 'doctor_max_price', $max, $target, $label.' (max)');
                            }
                        }
                        if ($subId === 0) {
                            $serviceLevelTargets[$mode] = $target;
                        }
                    } elseif ($adoptEqual && $explicit !== null && self::same($explicit, $target)) {
                        $original = $this->doctorOriginalLocationValue($locationId, $serviceId, $subId, $mode);
                        if ($original !== null && ! self::same($original, $target)) {
                            if (! isset($modeRow['original_'.$jsonField])) {
                                $pricing[$i]['modes'][$mode]['original_'.$jsonField] = $original;
                                $jsonChanged = true;
                            }
                            $this->log('doctor_pricing_json', (int) $doctor->id, $key, $jsonField, $original, $target, $label);
                        }
                    }
                }
            }
        }

        // Flat columns (legacy doctors without consultation_pricing JSON, or kept in sync with service-level JSON).
        $columnChanged = false;
        if (! $this->rule->sub_service_id) {
            $columns = $isPayout ? self::PAYOUT_COLUMNS : self::WEBSITE_FEE_COLUMNS;
            foreach ($modes as $mode) {
                $column = $columns[$mode];
                $current = self::num($doctor->{$column});
                if ($current === null) {
                    continue;
                }
                $target = $serviceLevelTargets[$mode] ?? null;
                if ($target === null && ! $hasJsonForService && $fallbackLocation
                    && $this->cityAllowed((int) $fallbackLocation->id) && $this->serviceMatches($titleServiceId, 0)) {
                    $target = self::targetPrice($this->rule, $this->doctorBaseValue((int) $fallbackLocation->id, $titleServiceId, 0, $mode));
                }
                if ($target === null || ! $this->needsChange($current, $target)) {
                    continue;
                }
                if ($this->rule->isTemporary() && $doctor->{'original_'.$column} === null) {
                    $doctor->{'original_'.$column} = $current;
                }
                $doctor->{$column} = $target;
                $columnChanged = true;
                $this->log('doctor_column', (int) $doctor->id, ['column' => $column], $column, $current, $target,
                    sprintf('%s · %s · %s', $who, $isPayout ? 'Consultation charge' : 'Website fee', self::FIELD_LABELS[$mode]));
                if (! isset($serviceLevelTargets[$mode])) {
                    $priceLogs[] = [$mode, $current, $target];
                }
            }
        }

        if ($jsonChanged) {
            $doctor->consultation_pricing = $pricing;
        }
        if ($jsonChanged || $columnChanged) {
            $doctor->save();
            foreach ($priceLogs as [$mode, $old, $new]) {
                DoctorRequestPriceLog::create([
                    'doctor_request_id' => $doctor->id,
                    'mode' => $mode,
                    'old_amount' => $old,
                    'new_amount' => $new,
                    'note' => ($isPayout ? 'Payout' : 'Website fee').' updated via Bulk Pricing Rule #'.$this->rule->id,
                    'updated_by' => $this->rule->created_by ?? 1,
                ]);
            }
        }
    }

    private function doctorOriginalLocationValue(int $locationId, int $serviceId, int $subId, string $mode): ?float
    {
        $field = $this->isPayout() ? 'doctor_max_price' : 'website_price';
        foreach (array_unique([$subId, 0]) as $sid) {
            $k = "{$locationId}|{$serviceId}|{$sid}|{$mode}|{$field}";
            if (array_key_exists($k, $this->locationOldValues)) {
                return $this->locationOldValues[$k];
            }
        }

        return null;
    }

    // ─── Revert ──────────────────────────────────────────────────────────

    private function revertLegacy(BulkPricingRule $rule): int
    {
        $restored = 0;
        $restoreRows = function ($query, array $fields) use (&$restored) {
            $query->get()->each(function ($row) use ($fields, &$restored) {
                $dirty = false;
                foreach ($fields as $field) {
                    if ($row->{'original_'.$field} !== null) {
                        $row->{$field} = $row->{'original_'.$field};
                        $row->{'original_'.$field} = null;
                        $dirty = true;
                    }
                }
                if ($dirty) {
                    $row->save();
                    $restored++;
                }
            });
        };

        DB::transaction(function () use ($rule, $restoreRows, &$restored) {
            if (in_array($rule->pricing_type, ['vendor', 'freelancer', 'website_general'], true)) {
                $model = match ($rule->pricing_type) {
                    'vendor' => VendorServicePrice::query(),
                    'freelancer' => JobRequestServicePrice::query(),
                    default => null,
                };
                if ($model) {
                    $restoreRows($model
                        ->when($rule->service_id, fn ($q) => $q->where('service_id', $rule->service_id))
                        ->when($rule->sub_service_id, fn ($q) => $q->where('service_sub_service_id', $rule->sub_service_id)), self::PROVIDER_FIELDS);
                }
                DB::table('location_services')
                    ->when($rule->service_id, fn ($q) => $q->where('service_id', $rule->service_id))
                    ->when($rule->sub_service_id, fn ($q) => $q->where('service_sub_service_id', $rule->sub_service_id))
                    ->get()
                    ->each(function ($row) use (&$restored) {
                        $update = [];
                        foreach (self::PROVIDER_FIELDS as $field) {
                            if ($row->{'original_'.$field} !== null) {
                                $update[$field] = $row->{'original_'.$field};
                                $update['original_'.$field] = null;
                            }
                        }
                        if ($update !== []) {
                            DB::table('location_services')->where('id', $row->id)->update($update);
                            $restored++;
                        }
                    });

                return;
            }

            $restoreRows(LocationDoctorConsultationPrice::query()
                ->when($rule->service_id, fn ($q) => $q->where('doctor_consultation_service_id', $rule->service_id)), ['website_price', 'doctor_max_price']);

            $isPayout = $rule->pricing_type === 'doctor_payout';
            $columns = array_values($isPayout ? self::PAYOUT_COLUMNS : self::WEBSITE_FEE_COLUMNS);
            $jsonField = $isPayout ? 'doctor_price' : 'website_price';
            DoctorRequest::query()->get()->each(function (DoctorRequest $doctor) use ($columns, $jsonField, &$restored) {
                $dirty = false;
                foreach ($columns as $column) {
                    if ($doctor->{'original_'.$column} !== null) {
                        $doctor->{$column} = $doctor->{'original_'.$column};
                        $doctor->{'original_'.$column} = null;
                        $dirty = true;
                    }
                }
                $pricing = $doctor->consultation_pricing;
                if (is_array($pricing)) {
                    foreach ($pricing as $i => $row) {
                        foreach (self::DOCTOR_MODES as $mode) {
                            if (isset($row['modes'][$mode]['original_'.$jsonField])) {
                                $pricing[$i]['modes'][$mode][$jsonField] = $row['modes'][$mode]['original_'.$jsonField];
                                unset($pricing[$i]['modes'][$mode]['original_'.$jsonField]);
                                $dirty = true;
                            }
                        }
                    }
                    $doctor->consultation_pricing = $pricing;
                }
                if ($dirty) {
                    $doctor->save();
                    $restored++;
                }
            });
        });

        return $restored;
    }

    /**
     * Restores the old value only when the price still holds the value this rule set
     * (a manual edit made in between is left alone).
     */
    private function revertChange(BulkPricingRuleChange $change): bool
    {
        $key = $change->target_key ?? [];
        $field = $change->field;

        switch ($change->target_type) {
            case 'location_service':
                $query = DB::table('location_services')
                    ->where('location_id', $key['location_id'] ?? 0)
                    ->where('service_id', $key['service_id'] ?? 0)
                    ->where('service_sub_service_id', $key['service_sub_service_id'] ?? 0)
                    ->where('provider_type', $key['provider_type'] ?? 'vendor');
                $row = (clone $query)->first();
                if (! $row || ! self::same($row->{$field}, $change->new_value)) {
                    return false;
                }
                $query->update([$field => $change->old_value, 'original_'.$field => null, 'updated_at' => now()]);

                return true;

            case 'location_doctor_price':
                $sub = (int) ($key['sub_service_id'] ?? 0);
                $row = LocationDoctorConsultationPrice::query()
                    ->where('location_id', $key['location_id'] ?? 0)
                    ->where('doctor_consultation_service_id', $key['service_id'] ?? 0)
                    ->when($sub > 0, fn ($q) => $q->where('doctor_consultation_service_sub_service_id', $sub), fn ($q) => $q->whereNull('doctor_consultation_service_sub_service_id'))
                    ->where('consultation_mode', $key['mode'] ?? '')
                    ->first();
                if (! $row || ! self::same($row->{$field}, $change->new_value)) {
                    return false;
                }
                $row->{$field} = $change->old_value;
                $row->{'original_'.$field} = null;
                $row->save();

                return true;

            case 'vendor_price':
            case 'freelancer_price':
                $isVendor = $change->target_type === 'vendor_price';
                $row = ($isVendor ? VendorServicePrice::query()->where('vendor_id', $key['vendor_id'] ?? 0)
                    : JobRequestServicePrice::query()->where('job_request_id', $key['job_request_id'] ?? 0))
                    ->where('service_id', $key['service_id'] ?? 0)
                    ->where('service_sub_service_id', $key['service_sub_service_id'] ?? 0)
                    ->first();
                if (! $row || ! self::same($row->{$field}, $change->new_value)) {
                    return false;
                }
                $row->{$field} = $change->old_value;
                $row->{'original_'.$field} = null;
                if ($row->price_12hr === null && $row->price_24hr === null && $row->price_onetime === null) {
                    $row->delete();
                } else {
                    $row->save();
                }

                return true;

            case 'doctor_pricing_json':
                $doctor = DoctorRequest::find($change->target_id);
                if (! $doctor || ! is_array($doctor->consultation_pricing)) {
                    return false;
                }
                $pricing = $doctor->consultation_pricing;
                $mode = (string) ($key['mode'] ?? '');
                foreach ($pricing as $i => $row) {
                    if (! is_array($row) || ! is_array($row['modes'][$mode] ?? null)) {
                        continue;
                    }
                    if ((int) ($row['sub_service_id'] ?? 0) !== (int) ($key['sub_service_id'] ?? 0)) {
                        continue;
                    }
                    if (! empty($row['service_id']) && (int) $row['service_id'] !== (int) ($key['service_id'] ?? 0)) {
                        continue;
                    }
                    if (! self::same($row['modes'][$mode][$field] ?? null, $change->new_value)) {
                        return false;
                    }
                    $pricing[$i]['modes'][$mode][$field] = $change->old_value !== null ? (float) $change->old_value : null;
                    unset($pricing[$i]['modes'][$mode]['original_'.$field]);
                    $doctor->consultation_pricing = $pricing;
                    $doctor->save();

                    return true;
                }

                return false;

            case 'doctor_column':
                $doctor = DoctorRequest::find($change->target_id);
                if (! $doctor || ! self::same($doctor->{$field}, $change->new_value)) {
                    return false;
                }
                $doctor->{$field} = $change->old_value;
                $doctor->{'original_'.$field} = null;
                $doctor->save();

                return true;
        }

        return false;
    }
}
