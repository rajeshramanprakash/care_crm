<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\VendorServicePrice;
use App\Models\VendorServicePriceChangeRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorServicePriceChangeService
{
    public function currentPrice(Vendor $vendor, int $serviceId, int $subServiceId, string $priceType): ?float
    {
        $resolved = VendorServiceSync::resolvePrice($vendor, $serviceId, $subServiceId);
        $field = $this->priceField($priceType);
        $value = $resolved[$field] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    public function submit(Vendor $vendor, array $payload): VendorServicePriceChangeRequest
    {
        $serviceId = (int) ($payload['service_id'] ?? 0);
        $subServiceId = (int) ($payload['service_sub_service_id'] ?? 0);
        $priceType = (string) ($payload['price_type'] ?? '');
        $requested = (float) ($payload['requested_price'] ?? 0);

        if ($serviceId <= 0) {
            throw ValidationException::withMessages(['service_id' => ['Invalid service.']]);
        }

        if (! in_array($priceType, VendorServicePriceChangeRequest::priceTypes(), true)) {
            throw ValidationException::withMessages(['price_type' => ['Invalid price type.']]);
        }

        if ($requested <= 0) {
            throw ValidationException::withMessages(['requested_price' => ['Please enter a valid price greater than zero.']]);
        }

        $this->assertItemAllowed($vendor, $serviceId, $subServiceId);

        $pendingExists = VendorServicePriceChangeRequest::query()
            ->where('vendor_id', $vendor->id)
            ->where('service_id', $serviceId)
            ->where('service_sub_service_id', $subServiceId)
            ->where('price_type', $priceType)
            ->where('status', VendorServicePriceChangeRequest::STATUS_PENDING)
            ->exists();

        if ($pendingExists) {
            throw ValidationException::withMessages([
                'requested_price' => ['Is service/type ke liye request pehle se pending hai.'],
            ]);
        }

        $current = $this->currentPrice($vendor, $serviceId, $subServiceId, $priceType);
        if ($current !== null && round($current, 2) === round($requested, 2)) {
            throw ValidationException::withMessages([
                'requested_price' => ['Requested price aapki current price ke barabar hai.'],
            ]);
        }

        $items = $this->pricingItemsForVendor($vendor);
        $item = collect($items)->first(fn ($row) =>
            (int) $row['service_id'] === $serviceId &&
            (int) $row['service_sub_service_id'] === $subServiceId
        );

        return VendorServicePriceChangeRequest::create([
            'vendor_id' => $vendor->id,
            'service_id' => $serviceId,
            'service_name' => (string) ($item['service_name'] ?? ''),
            'service_sub_service_id' => $subServiceId,
            'sub_service_name' => $subServiceId > 0 ? ($item['label'] ?? 'Sub-service') : null,
            'price_type' => $priceType,
            'current_price' => $current,
            'requested_price' => $requested,
            'status' => VendorServicePriceChangeRequest::STATUS_PENDING,
        ]);
    }

    public function approve(VendorServicePriceChangeRequest $changeRequest, ?int $adminUserId = null): void
    {
        if ($changeRequest->status !== VendorServicePriceChangeRequest::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => ['This request has already been reviewed.']]);
        }

        DB::transaction(function () use ($changeRequest, $adminUserId) {
            $vendor = Vendor::query()->lockForUpdate()->find($changeRequest->vendor_id);
            if (! $vendor) {
                throw ValidationException::withMessages(['vendor' => ['Vendor not found.']]);
            }

            $this->applyPriceToVendor($vendor, $changeRequest);

            $changeRequest->status = VendorServicePriceChangeRequest::STATUS_APPROVED;
            $changeRequest->admin_note = null;
            $changeRequest->reviewed_by = $adminUserId;
            $changeRequest->reviewed_at = now();
            $changeRequest->save();

            VendorServicePriceChangeRequest::query()
                ->where('vendor_id', $vendor->id)
                ->where('service_id', (int) $changeRequest->service_id)
                ->where('service_sub_service_id', (int) $changeRequest->service_sub_service_id)
                ->where('price_type', $changeRequest->price_type)
                ->where('status', VendorServicePriceChangeRequest::STATUS_PENDING)
                ->where('id', '!=', $changeRequest->id)
                ->update([
                    'status' => VendorServicePriceChangeRequest::STATUS_REJECTED,
                    'admin_note' => 'Superseded by approved request #'.$changeRequest->id.'.',
                    'reviewed_by' => $adminUserId,
                    'reviewed_at' => now(),
                ]);
        });
    }

    public function reject(VendorServicePriceChangeRequest $changeRequest, string $adminNote, ?int $adminUserId = null): void
    {
        if ($changeRequest->status !== VendorServicePriceChangeRequest::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => ['This request has already been reviewed.']]);
        }

        $adminNote = trim($adminNote);
        if ($adminNote === '') {
            throw ValidationException::withMessages(['admin_note' => ['Please enter a reason for rejection.']]);
        }

        $changeRequest->status = VendorServicePriceChangeRequest::STATUS_REJECTED;
        $changeRequest->admin_note = $adminNote;
        $changeRequest->reviewed_by = $adminUserId;
        $changeRequest->reviewed_at = now();
        $changeRequest->save();
    }

    /**
     * @return list<array{service_id: int, service_name: string, service_sub_service_id: int, label: string, tags: list<string>}>
     */
    public function pricingItemsForVendor(Vendor $vendor): array
    {
        $items = [];
        foreach (VendorServiceSync::blocksForVendor($vendor) as $block) {
            $serviceId = (int) ($block['service_id'] ?? 0);
            $serviceName = (string) ($block['service_name'] ?? '');
            if ($serviceId <= 0) {
                continue;
            }

            $subs = is_array($block['sub_services'] ?? null) ? $block['sub_services'] : [];
            $hasRealSubs = false;
            foreach ($subs as $entry) {
                if ((int) ($entry['sub_service_id'] ?? 0) > 0) {
                    $hasRealSubs = true;
                    break;
                }
            }

            if ($hasRealSubs) {
                foreach ($subs as $entry) {
                    $subId = (int) ($entry['sub_service_id'] ?? 0);
                    if ($subId <= 0) {
                        continue;
                    }
                    $items[] = [
                        'service_id' => $serviceId,
                        'service_name' => $serviceName,
                        'service_sub_service_id' => $subId,
                        'label' => $entry['sub_service_name'] ?? 'Sub-service',
                        'tags' => is_array($entry['tags'] ?? null) ? $entry['tags'] : [],
                    ];
                }
            } else {
                $tags = [];
                foreach ($subs as $entry) {
                    if ((int) ($entry['sub_service_id'] ?? 0) === 0) {
                        $tags = is_array($entry['tags'] ?? null) ? $entry['tags'] : [];
                    }
                }
                $items[] = [
                    'service_id' => $serviceId,
                    'service_name' => $serviceName,
                    'service_sub_service_id' => 0,
                    'label' => $serviceName,
                    'tags' => $tags,
                ];
            }
        }

        return $items;
    }

    private function applyPriceToVendor(Vendor $vendor, VendorServicePriceChangeRequest $changeRequest): void
    {
        $serviceId = (int) $changeRequest->service_id;
        $subId = (int) $changeRequest->service_sub_service_id;
        $field = $this->priceField((string) $changeRequest->price_type);
        $newPrice = (float) $changeRequest->requested_price;

        $override = VendorServicePrice::query()->firstOrNew([
            'vendor_id' => $vendor->id,
            'service_id' => $serviceId,
            'service_sub_service_id' => $subId,
        ]);

        $resolved = VendorServiceSync::resolvePrice($vendor, $serviceId, $subId);
        if (! $override->exists) {
            $override->price_12hr = $resolved['price_12hr'];
            $override->price_24hr = $resolved['price_24hr'];
            $override->price_onetime = $resolved['price_onetime'];
        }

        $override->{$field} = $newPrice;
        $override->save();

        $this->syncJsonOverride($vendor, $serviceId, $subId, $field, $newPrice);
    }

    private function syncJsonOverride(Vendor $vendor, int $serviceId, int $subId, string $field, float $newPrice): void
    {
        $blocks = VendorServiceSync::blocksForVendor($vendor);
        $updated = false;
        foreach ($blocks as $i => $block) {
            if ((int) ($block['service_id'] ?? 0) !== $serviceId) {
                continue;
            }
            $overrides = is_array($block['price_overrides'] ?? null) ? $block['price_overrides'] : [];
            $found = false;
            foreach ($overrides as $j => $row) {
                if ((int) ($row['service_sub_service_id'] ?? 0) === $subId) {
                    $overrides[$j][$field] = (string) $newPrice;
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $overrides[] = [
                    'service_sub_service_id' => $subId,
                    'price_12hr' => $field === 'price_12hr' ? (string) $newPrice : null,
                    'price_24hr' => $field === 'price_24hr' ? (string) $newPrice : null,
                    'price_onetime' => $field === 'price_onetime' ? (string) $newPrice : null,
                ];
            }
            $blocks[$i]['price_overrides'] = $overrides;
            $updated = true;
            break;
        }
        if ($updated) {
            $vendor->vendor_services = $blocks;
            $vendor->service_city_shifts = VendorServiceSync::buildLegacyServiceCityShifts($blocks, (string) $vendor->location);
            $vendor->save();
        }
    }

    private function priceField(string $priceType): string
    {
        return match ($priceType) {
            VendorServicePriceChangeRequest::TYPE_12HR => 'price_12hr',
            VendorServicePriceChangeRequest::TYPE_24HR => 'price_24hr',
            VendorServicePriceChangeRequest::TYPE_ONETIME => 'price_onetime',
            default => throw ValidationException::withMessages(['price_type' => ['Invalid price type.']]),
        };
    }

    private function assertItemAllowed(Vendor $vendor, int $serviceId, int $subServiceId): void
    {
        $allowed = false;
        foreach ($this->pricingItemsForVendor($vendor) as $item) {
            if ((int) $item['service_id'] === $serviceId && (int) $item['service_sub_service_id'] === $subServiceId) {
                $allowed = true;
                break;
            }
        }
        if (! $allowed) {
            throw ValidationException::withMessages(['service_id' => ['Yeh service aapki profile par set nahi hai.']]);
        }
    }
}
