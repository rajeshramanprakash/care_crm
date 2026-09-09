<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Service;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VendorController extends Controller
{
    public function index()
    {
        // Check if this is an API request
        if (request()->expectsJson()) {
            $vendors = Vendor::latest()->get();
            return response()->json($vendors);
        }

        $vendors = Vendor::latest()->get();
        $services = Service::all();
        $locations = Location::all();
        return view('admin.vendor.index', compact('vendors', 'services', 'locations'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_no' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'upi_id' => 'nullable|string|max:100',
            'service_city_shifts' => 'nullable|array',
            'shift' => 'nullable|in:12,24,both',
            'status' => 'nullable|in:active,dutyoff'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()]);
        }

        Vendor::create($request->all());
        return response()->json(['status' => 'success', 'message' => 'Vendor created successfully']);
    }

    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);
        return response()->json($vendor);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_no' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'upi_id' => 'nullable|string|max:100',
            'service_city_shifts' => 'nullable|array',
            'shift' => 'nullable|in:12,24,both',
            'status' => 'nullable|in:active,dutyoff'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()]);
        }

        $vendor = Vendor::findOrFail($id);
        $vendor->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Vendor updated successfully']);
    }

    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();
        return response()->json(['status' => 'success', 'message' => 'Vendor deleted successfully']);
    }
}
