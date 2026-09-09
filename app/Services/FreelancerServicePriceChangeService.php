<?php

namespace App\Services;

use App\Models\FreelancerServicePriceChangeRequest;
use App\Models\JobRequest;
use App\Models\JobRequestServicePrice;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FreelancerServicePriceChangeService
{
    public function currentPrice(JobRequest $freelancer, int $serviceId, int $subServiceId, string $priceType): ?float
    {
        $resolved = JobRequestFreelancerServiceSync::resolvePrice($freelancer, $serviceId, $subServiceId);
        $field = $this->priceField($priceType);
        $value = $resolved[$field] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    public function submit(JobRequest $freelancer, array $payload): FreelancerServicePriceChangeRequest
    {
        $subServiceId = (int) ($payload['service_sub_service_id'] ?? 0);
        $priceType = (string) ($payload['price_type'] ?? '');
        $requested = (float) ($payload['requested_price'] ?? 0);

        if (! in_array($priceType, [
            FreelancerServicePriceChangeRequest::TYPE_12HR,
            FreelancerServicePriceChangeRequest::TYPE_24HR,
            FreelancerServicePriceChangeRequest::TYPE_ONETIME,
        ], true)) {
            throw ValidationException::withMessages(['price_type' => ['Invalid price type.']]);
        }

        $allowedTypes = FreelancerServicePriceChangeRequest::priceTypesForShift($freelancer->shift);
        if (! in_array($priceType, $allowedTypes, true)) {
            throw ValidationException::withMessages(['price_type' => ['This price type is not applicable for your shift.']]);
        }

        if ($requested <= 0) {
            throw ValidationException::withMessages(['requested_price' => ['Please enter a valid price greater than zero.']]);
        }

        $serviceRow = Service::query()
            ->where('name', $freelancer->job_title)
            ->first();

        if (! $serviceRow) {
            throw ValidationException::withMessages(['service' => ['Service is not set on your profile.']]);
        }

        $this->assertSubServiceAllowed($freelancer, $subServiceId);

        $pendingExists = FreelancerServicePriceChangeRequest::query()
            ->where('job_request_id', $freelancer->id)
            ->where('service_sub_service_id', $subServiceId)
            ->where('price_type', $priceType)
            ->where('status', FreelancerServicePriceChangeRequest::STATUS_PENDING)
            ->exists();

        if ($pendingExists) {
            throw ValidationException::withMessages([
                'requested_price' => ['A price change request for this service and type is already pending admin review.'],
            ]);
        }

        $current = $this->currentPrice($freelancer, (int) $serviceRow->id, $subServiceId, $priceType);
        if ($current !== null && round($current, 2) === round($requested, 2)) {
            throw ValidationException::withMessages([
                'requested_price' => ['Requested price is the same as your current price.'],
            ]);
        }

        $subName = $this->subServiceName($freelancer, $subServiceId);

        return FreelancerServicePriceChangeRequest::create([
            'job_request_id' => $freelancer->id,
            'service_id' => (int) $serviceRow->id,
            'service_name' => (string) $serviceRow->name,
            'service_sub_service_id' => $subServiceId,
            'sub_service_name' => $subName,
            'price_type' => $priceType,
            'current_price' => $current,
            'requested_price' => $requested,
            'status' => FreelancerServicePriceChangeRequest::STATUS_PENDING,
        ]);
    }

    public function approve(FreelancerServicePriceChangeRequest $changeRequest, ?int $adminUserId = null): void
    {
        if ($changeRequest->status !== FreelancerServicePriceChangeRequest::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => ['This request has already been reviewed.']]);
        }

        DB::transaction(function () use ($changeRequest, $adminUserId) {
            $freelancer = $changeRequest->jobRequest()->lockForUpdate()->first();
            if (! $freelancer) {
                throw ValidationException::withMessages(['freelancer' => ['Freelancer not found.']]);
            }

            $this->applyPriceToFreelancer($freelancer, $changeRequest);

            $changeRequest->status = FreelancerServicePriceChangeRequest::STATUS_APPROVED;
            $changeRequest->admin_note = null;
            $changeRequest->reviewed_by = $adminUserId;
            $changeRequest->reviewed_at = now();
            $changeRequest->save();

            FreelancerServicePriceChangeRequest::query()
                ->where('job_request_id', $freelancer->id)
                ->where('service_sub_service_id', (int) $changeRequest->service_sub_service_id)
                ->where('price_type', $changeRequest->price_type)
                ->where('status', FreelancerServicePriceChangeRequest::STATUS_PENDING)
                ->where('id', '!=', $changeRequest->id)
                ->update([
                    'status' => FreelancerServicePriceChangeRequest::STATUS_REJECTED,
                    'admin_note' => 'Superseded by approved request #'.$changeRequest->id.'.',
                    'reviewed_by' => $adminUserId,
                    'reviewed_at' => now(),
                ]);
        });
    }

    public function reject(FreelancerServicePriceChangeRequest $changeRequest, string $adminNote, ?int $adminUserId = null): void
    {
        if ($changeRequest->status !== FreelancerServicePriceChangeRequest::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => ['This request has already been reviewed.']]);
        }

        $adminNote = trim($adminNote);
        if ($adminNote === '') {
            throw ValidationException::withMessages(['admin_note' => ['Please enter a reason for rejection.']]);
        }

        $changeRequest->status = FreelancerServicePriceChangeRequest::STATUS_REJECTED;
        $changeRequest->admin_note = $adminNote;
        $changeRequest->reviewed_by = $adminUserId;
        $changeRequest->reviewed_at = now();
        $changeRequest->save();
    }

    /**
     * @return list<array{service_sub_service_id: int, label: string, tags: list<string>}>
     */
    public function pricingItemsForFreelancer(JobRequest $freelancer): array
    {
        $serviceRow = Service::query()->where('name', $freelancer->job_title)->first();
        if (! $serviceRow) {
            return [];
        }

        $subs = is_array($freelancer->service_sub_services) ? $freelancer->service_sub_services : [];
        $items = [];

        foreach ($subs as $entry) {
            $subId = (int) ($entry['sub_service_id'] ?? 0);
            if ($subId <= 0) {
                continue;
            }
            $items[] = [
                'service_sub_service_id' => $subId,
                'label' => (string) ($entry['sub_service_name'] ?? 'Sub-service'),
                'tags' => is_array($entry['tags'] ?? null) ? $entry['tags'] : [],
            ];
        }

        if ($items === []) {
            $tags = [];
            foreach ($subs as $entry) {
                if ((int) ($entry['sub_service_id'] ?? -1) === 0) {
                    $tags = is_array($entry['tags'] ?? null) ? $entry['tags'] : [];
                    break;
                }
            }
            $items[] = [
                'service_sub_service_id' => 0,
                'label' => (string) $freelancer->job_title,
                'tags' => $tags,
            ];
        }

        return $items;
    }

    private function applyPriceToFreelancer(JobRequest $freelancer, FreelancerServicePriceChangeRequest $changeRequest): void
    {
        $serviceId = (int) $changeRequest->service_id;
        $subId = (int) $changeRequest->service_sub_service_id;
        $field = $this->priceField((string) $changeRequest->price_type);
        $newPrice = (float) $changeRequest->requested_price;

        $override = JobRequestServicePrice::query()->firstOrNew([
            'job_request_id' => $freelancer->id,
            'service_id' => $serviceId,
            'service_sub_service_id' => $subId,
        ]);

        $resolved = JobRequestFreelancerServiceSync::resolvePrice($freelancer, $serviceId, $subId);
        if (! $override->exists) {
            $override->price_12hr = $resolved['price_12hr'];
            $override->price_24hr = $resolved['price_24hr'];
            $override->price_onetime = $resolved['price_onetime'];
        }

        $override->{$field} = $newPrice;
        $override->save();
    }

    private function priceField(string $priceType): string
    {
        return match ($priceType) {
            FreelancerServicePriceChangeRequest::TYPE_12HR => 'price_12hr',
            FreelancerServicePriceChangeRequest::TYPE_24HR => 'price_24hr',
            FreelancerServicePriceChangeRequest::TYPE_ONETIME => 'price_onetime',
            default => throw ValidationException::withMessages(['price_type' => ['Invalid price type.']]),
        };
    }

    private function assertSubServiceAllowed(JobRequest $freelancer, int $subServiceId): void
    {
        $items = $this->pricingItemsForFreelancer($freelancer);
        $allowedIds = array_map(fn ($row) => (int) $row['service_sub_service_id'], $items);

        if ($allowedIds === []) {
            throw ValidationException::withMessages(['service_sub_service_id' => ['No services linked to your profile.']]);
        }

        if (! in_array($subServiceId, $allowedIds, true)) {
            throw ValidationException::withMessages(['service_sub_service_id' => ['This service/sub-service is not linked to your profile.']]);
        }
    }

    private function subServiceName(JobRequest $freelancer, int $subServiceId): ?string
    {
        if ($subServiceId <= 0) {
            return null;
        }

        foreach (is_array($freelancer->service_sub_services) ? $freelancer->service_sub_services : [] as $entry) {
            if ((int) ($entry['sub_service_id'] ?? 0) === $subServiceId) {
                return (string) ($entry['sub_service_name'] ?? 'Sub-service');
            }
        }

        return 'Sub-service';
    }
}
