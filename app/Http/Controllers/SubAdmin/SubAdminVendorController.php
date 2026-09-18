<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Service;
use App\Models\Vendor;
use App\Models\VendorServicePriceChangeRequest;
use App\Services\VendorServiceSync;
use App\Services\VendorServicePriceChangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SubAdminVendorController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_vendors');
    }

    public function index()
    {
        if (request()->expectsJson()) {
            $pendingByVendor = VendorServicePriceChangeRequest::query()
                ->where('status', VendorServicePriceChangeRequest::STATUS_PENDING)
                ->selectRaw('vendor_id, count(*) as cnt')
                ->groupBy('vendor_id')
                ->pluck('cnt', 'vendor_id');

            $vendors = Vendor::latest()->get()->map(function ($v) use ($pendingByVendor) {
                $data = $this->formatVendorForJson($v);
                $data['pending_price_change_count'] = (int) ($pendingByVendor[$v->id] ?? 0);

                return $data;
            });

            return response()->json($vendors);
        }

        $vendors = Vendor::latest()->get()->map(function ($vendor) {
            if ($vendor->service_city_shifts && is_string($vendor->service_city_shifts)) {
                $vendor->service_city_shifts = json_decode($vendor->service_city_shifts, true);
            }

            return $vendor;
        });

        $services = Service::with(['subServices' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $locations = Location::orderBy('name', 'asc')->get();

        $pendingPriceChangeCount = VendorServicePriceChangeRequest::query()
            ->where('status', VendorServicePriceChangeRequest::STATUS_PENDING)
            ->count();

        $pendingByVendor = VendorServicePriceChangeRequest::query()
            ->where('status', VendorServicePriceChangeRequest::STATUS_PENDING)
            ->selectRaw('vendor_id, count(*) as cnt')
            ->groupBy('vendor_id')
            ->pluck('cnt', 'vendor_id');

        return view('subadmin.vendor.index', compact('vendors', 'services', 'locations', 'pendingPriceChangeCount', 'pendingByVendor'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_no' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'location' => 'required|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'upi_id' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,dutyoff,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()]);
        }

        if ($err = VendorServiceSync::validateRequest($request)) {
            return response()->json(['status' => 'error', 'errors' => $err['errors'], 'message' => $err['message']], 422);
        }

        $data = $request->only([
            'name', 'contact_no', 'email', 'location', 'account_name',
            'account_number', 'ifsc_code', 'upi_id', 'status',
        ]);
        $data['status'] = $data['status'] ?? 'active';

        $latestVendor = Vendor::orderBy('id', 'desc')->first();
        $nextNumber = $latestVendor ? $latestVendor->id + 1 : 1;
        $data['lead_id'] = 'CLX-VEN-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

        $vendor = Vendor::create($data);
        VendorServiceSync::applyToVendor($vendor, $request);

        return response()->json(['status' => 'success', 'message' => 'Vendor created successfully']);
    }

    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);

        return response()->json($this->formatVendorForJson($vendor));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_no' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'location' => 'required|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'upi_id' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,dutyoff,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()]);
        }

        if ($err = VendorServiceSync::validateRequest($request)) {
            return response()->json(['status' => 'error', 'errors' => $err['errors'], 'message' => $err['message']], 422);
        }

        $vendor = Vendor::findOrFail($id);
        $vendor->fill($request->only([
            'name', 'contact_no', 'email', 'location', 'account_name',
            'account_number', 'ifsc_code', 'upi_id', 'status',
        ]));
        $vendor->save();

        VendorServiceSync::applyToVendor($vendor, $request);

        return response()->json(['status' => 'success', 'message' => 'Vendor updated successfully']);
    }

    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        return response()->json(['status' => 'success', 'message' => 'Vendor deleted successfully']);
    }

    public function priceChangeRequests(Vendor $vendor): JsonResponse
    {
        $requests = $vendor->priceChangeRequests()
            ->where('status', VendorServicePriceChangeRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (VendorServicePriceChangeRequest $req) {
                return [
                    'id' => $req->id,
                    'service_name' => $req->service_name,
                    'service_sub_service_id' => (int) $req->service_sub_service_id,
                    'sub_service_name' => $req->sub_service_name,
                    'price_type' => $req->price_type,
                    'price_type_label' => $req->priceTypeLabel(),
                    'current_price' => $req->current_price,
                    'requested_price' => $req->requested_price,
                    'created_at' => $req->created_at?->format('d M Y, H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'location' => $vendor->location,
            ],
            'requests' => $requests,
        ]);
    }

    public function approvePriceChangeRequest(VendorServicePriceChangeRequest $price_change_request): JsonResponse
    {
        try {
            app(VendorServicePriceChangeService::class)->approve($price_change_request, Auth::id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Could not approve request.';

            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Price change approved. Sirf is vendor ki price update ho gayi.',
        ]);
    }

    public function rejectPriceChangeRequest(Request $request, VendorServicePriceChangeRequest $price_change_request): JsonResponse
    {
        $request->validate(['admin_note' => ['required', 'string', 'max:2000']]);

        try {
            app(VendorServicePriceChangeService::class)->reject(
                $price_change_request,
                (string) $request->input('admin_note'),
                Auth::id()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Could not reject request.';

            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        return response()->json(['success' => true, 'message' => 'Price change request rejected.']);
    }

    private function formatVendorForJson(Vendor $vendor): array
    {
        $vendor->loadMissing('latestLeegalitySignature');
        $data = $vendor->toArray();
        if (is_string($data['service_city_shifts'] ?? null)) {
            $data['service_city_shifts'] = json_decode($data['service_city_shifts'], true);
        }
        $data['vendor_services'] = VendorServiceSync::blocksForVendor($vendor);
        $data['partner_id'] = $vendor->partner_id ?? $vendor->lead_id;
        $data['agreement_number'] = $vendor->agreement_number ?? '—';
        $sig = $vendor->latestLeegalitySignature;
        $data['agreement_status'] = $sig?->signature_status;
        $data['agreement_status_label'] = $sig ? ($sig->statusEmoji().' '.$sig->statusLabel()) : '—';
        $data['leegality_html'] = view('subadmin.vendors.partials.leegality_agreement_panel', [
            'vendor' => $vendor,
            'leegalitySignature' => $sig,
        ])->render();

        return $data;
    }
}
