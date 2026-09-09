<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Service;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = Location::with('services')->latest()->get();
        return view('admin.locations.index', compact('locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $services = Service::all();
        return view('admin.locations.create', compact('services'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:locations',
            'services' => 'array',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.price_12hr' => 'nullable|numeric|min:0',
            'services.*.price_24hr' => 'nullable|numeric|min:0',
            'services.*.price_onetime' => 'nullable|numeric|min:0'
        ]);

        $location = Location::create($request->only('name'));

        // Attach services with pricing
        if ($request->has('services')) {
            foreach ($request->services as $serviceData) {
                if (!empty($serviceData['service_id'])) {
                    $location->services()->attach($serviceData['service_id'], [
                        'price_12hr' => $serviceData['price_12hr'] ?? null,
                        'price_24hr' => $serviceData['price_24hr'] ?? null,
                        'price_onetime' => $serviceData['price_onetime'] ?? null
                    ]);
                }
            }
        }

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Location $location)
    {
        $services = Service::all();
        $location->load('services');
        return view('admin.locations.edit', compact('location', 'services'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Location $location)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:locations,name,' . $location->id,
            'services' => 'array',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.price_12hr' => 'nullable|numeric|min:0',
            'services.*.price_24hr' => 'nullable|numeric|min:0',
            'services.*.price_onetime' => 'nullable|numeric|min:0'
        ]);

        $location->update($request->only('name'));

        // Sync services with pricing
        $location->services()->detach();
        if ($request->has('services')) {
            foreach ($request->services as $serviceData) {
                if (!empty($serviceData['service_id'])) {
                    $location->services()->attach($serviceData['service_id'], [
                        'price_12hr' => $serviceData['price_12hr'] ?? null,
                        'price_24hr' => $serviceData['price_24hr'] ?? null,
                        'price_onetime' => $serviceData['price_onetime'] ?? null
                    ]);
                }
            }
        }

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        $location->delete();

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location deleted successfully.');
    }

    /**
     * Get service rates for a specific location and service
     */
    public function getServiceRates(Request $request)
    {
        $request->validate([
            'location' => 'required|string',
            'service' => 'required|string'
        ]);

        // Debug logging
        \Log::info('Service rates request', [
            'location' => $request->location,
            'service' => $request->service
        ]);

        $location = Location::where('name', $request->location)->first();
        $service = Service::where('name', $request->service)->first();

        \Log::info('Lookup results', [
            'location_found' => $location ? $location->name : 'Not found',
            'service_found' => $service ? $service->name : 'Not found'
        ]);

        if (!$location || !$service) {
            return response()->json([
                'success' => false,
                'message' => 'Location or service not found'
            ], 404);
        }

        $locationService = $location->services()->where('service_id', $service->id)->first();

        if (!$locationService) {
            return response()->json([
                'success' => false,
                'message' => 'Service not available for this location'
            ], 404);
        }



        return response()->json([
            'success' => true,
            'data' => [
                'service_name' => $service->name,
                'service_description' => $service->description,
                'location_name' => $location->name,
                'prices' => [
                    'price_12hr' => $locationService->pivot->price_12hr,
                    'price_24hr' => $locationService->pivot->price_24hr,
                    'price_onetime' => $locationService->pivot->price_onetime
                ]
            ]
        ]);
    }
}
