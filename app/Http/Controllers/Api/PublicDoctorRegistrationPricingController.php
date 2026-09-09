<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationDoctorConsultationPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicDoctorRegistrationPricingController extends Controller
{
    public function show(Request $request)
    {
        $v = Validator::make($request->all(), [
            'location_id' => 'required|integer|exists:locations,id',
            'service_id' => 'required|integer|exists:doctor_consultation_services,id',
        ]);

        if ($v->fails()) {
            return response()->json([
                'success' => false,
                'message' => $v->errors()->first(),
                'errors' => $v->errors(),
            ], 422);
        }

        $locationId = (int) $request->input('location_id');
        $serviceId = (int) $request->input('service_id');

        $rows = LocationDoctorConsultationPrice::query()
            ->where('location_id', $locationId)
            ->where('doctor_consultation_service_id', $serviceId)
            ->where('is_active', true)
            ->get([
                'doctor_consultation_service_sub_service_id',
                'consultation_mode',
                'website_price',
                'doctor_max_price',
            ]);

        $out = [];
        foreach ($rows as $row) {
            $subId = $row->doctor_consultation_service_sub_service_id ? (int) $row->doctor_consultation_service_sub_service_id : 0;
            $mode = (string) $row->consultation_mode;
            $out[$subId] = $out[$subId] ?? [];
            $out[$subId][$mode] = [
                'website_price' => $row->website_price,
                'doctor_max_price' => $row->doctor_max_price,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'prices' => $out,
            ],
        ]);
    }
}

