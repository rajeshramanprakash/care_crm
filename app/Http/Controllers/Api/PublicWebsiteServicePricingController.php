<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceSubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Website (careweb) prices for general services, set per city in Admin > Locations > Website Pricing.
 */
class PublicWebsiteServicePricingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $locationName = trim((string) $request->query('location', ''));
        $serviceName = trim((string) $request->query('service', ''));
        $serviceId = (int) $request->query('service_id', 0);

        if ($locationName === '' || ($serviceName === '' && $serviceId <= 0)) {
            return response()->json(['success' => false, 'message' => 'location and service are required', 'prices' => []], 422);
        }

        $location = Location::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($locationName)])->first();
        $service = $serviceId > 0
            ? Service::query()->find($serviceId)
            : Service::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($serviceName)])->first();

        if (! $location || ! $service) {
            return response()->json(['success' => true, 'prices' => []]);
        }

        $rows = DB::table('location_services')
            ->where('location_id', $location->id)
            ->where('service_id', $service->id)
            ->where('provider_type', 'website')
            ->orderBy('service_sub_service_id')
            ->get();

        $subNames = ServiceSubService::query()
            ->whereIn('id', $rows->pluck('service_sub_service_id')->filter()->all())
            ->pluck('name', 'id');

        $toFloat = fn ($v) => ($v === null || $v === '') ? null : (float) $v;

        return response()->json([
            'success' => true,
            'location' => $location->name,
            'service' => $service->name,
            'prices' => $rows->map(fn ($row) => [
                'sub_service_id' => (int) $row->service_sub_service_id,
                'sub_service_name' => (int) $row->service_sub_service_id > 0 ? ($subNames[(int) $row->service_sub_service_id] ?? null) : null,
                'price_12hr' => $toFloat($row->price_12hr),
                'price_24hr' => $toFloat($row->price_24hr),
                'price_onetime' => $toFloat($row->price_onetime),
            ])->values(),
        ]);
    }
}
