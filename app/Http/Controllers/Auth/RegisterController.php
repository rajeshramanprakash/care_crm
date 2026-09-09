<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\JobRequest;
use App\Models\DoctorRequest;
use App\Models\Vendor;
use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\Service;
use App\Models\DoctorConsultationService;
use App\Models\Location;
use App\Models\LocationDoctorConsultationPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\DoctorRegistrationOtpService;
use App\Services\RegistrationOtpService;
use App\Services\RegistrationI18nService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('register');
    }

    public function showFormByType($type, RegistrationI18nService $i18n)
    {
        if (!in_array($type, ['customer', 'vendor', 'freelancer', 'doctor'])) {
            return redirect()->route('register')->with('error', 'Invalid registration type');
        }

        // Get services and locations for dropdowns (needed for vendor and freelancer)
        $services = Service::all();
        $locations = Location::query()->orderBy('name')->get();
        $doctorConsultationServices = DoctorConsultationService::query()
            ->where('is_active', true)
            ->with(['subServices' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $registrationAddressSearchEnabled = in_array($type, ['customer', 'doctor', 'freelancer'], true)
            && app(\App\Services\ConsultationAddressSearchService::class)->isConfigured();

        return view('register-form', [
            'type' => $type,
            'services' => $services,
            'doctor_consultation_services' => $doctorConsultationServices,
            'locations' => $locations,
            'registration_address_search_enabled' => $registrationAddressSearchEnabled,
            'doctor_address_search_enabled' => $type === 'doctor' && $registrationAddressSearchEnabled,
            'carecrm_api_base' => url('/api'),
            'registration_provider_type' => in_array($type, ['vendor', 'freelancer'], true) ? $type : null,
            'registration_languages' => $i18n->activeLanguages(),
        ]);
    }

    public function registrationLanguages(RegistrationI18nService $i18n)
    {
        return response()->json([
            'languages' => $i18n->activeLanguages()->map(fn ($lang) => [
                'id' => $lang->id,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'code' => $lang->code,
                'label' => $lang->displayLabel(),
            ])->values(),
        ]);
    }

    public function registrationTranslations(string $code, RegistrationI18nService $i18n)
    {
        $language = $i18n->findByCode($code);
        if (! $language) {
            return response()->json(['message' => 'Language not found'], 404);
        }

        return response()->json([
            'code' => $language->code,
            'name' => $language->name,
            'translations' => $i18n->translationsForLanguage($language),
        ]);
    }

    public function locationProviderServices(Request $request)
    {
        $request->validate([
            'location' => 'required|string|max:255',
            'provider_type' => 'required|in:vendor,freelancer',
        ]);

        $location = Location::query()->where('name', $request->location)->first();
        if (! $location) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found',
            ], 404);
        }

        $providerType = (string) $request->provider_type;

        $pivotRows = DB::table('location_services')
            ->where('location_id', $location->id)
            ->where('provider_type', $providerType)
            ->get();

        if ($pivotRows->isEmpty()) {
            return response()->json([
                'success' => true,
                'services' => [],
            ]);
        }

        $serviceIds = $pivotRows->pluck('service_id')->unique()->values()->all();

        $services = Service::query()
            ->whereIn('id', $serviceIds)
            ->with(['subServices' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $payload = [];
        foreach ($services as $service) {
            $prices = [];
            $servicePivotRows = $pivotRows->where('service_id', $service->id);
            foreach ($servicePivotRows as $row) {
                $subKey = (string) (int) ($row->service_sub_service_id ?? 0);
                $prices[$subKey] = [
                    'price_12hr' => $row->price_12hr,
                    'price_24hr' => $row->price_24hr,
                    'price_onetime' => $row->price_onetime,
                ];
            }

            $pricedSubIds = $servicePivotRows
                ->filter(fn ($row) => (int) ($row->service_sub_service_id ?? 0) > 0)
                ->pluck('service_sub_service_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($pricedSubIds->isNotEmpty()) {
                $subServicesPayload = $service->subServices
                    ->filter(fn ($sub) => $pricedSubIds->contains($sub->id))
                    ->map(fn ($sub) => [
                        'id' => (int) $sub->id,
                        'name' => (string) $sub->name,
                        'tags' => is_array($sub->specialization_options) ? array_values($sub->specialization_options) : [],
                    ])->values()->all();
            } else {
                $subServicesPayload = [];
            }

            $payload[] = [
                'id' => (int) $service->id,
                'name' => (string) $service->name,
                'tags' => is_array($service->specialization_options) ? array_values($service->specialization_options) : [],
                'sub_services' => $subServicesPayload,
                'prices' => $prices,
            ];
        }

        return response()->json([
            'success' => true,
            'services' => $payload,
        ]);
    }

    public function register(Request $request)
    {
        $type = $request->input('type');

        if (!in_array($type, ['customer', 'vendor', 'freelancer', 'doctor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid registration type'
            ], 422);
        }

        switch ($type) {
            case 'customer':
                return $this->registerCustomer($request);
            case 'vendor':
                return $this->registerVendor($request);
            case 'freelancer':
                return $this->registerFreelancer($request);
            case 'doctor':
                return $this->registerDoctor($request);
        }
    }

    private function registerCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'contact_no' => 'required|string|max:20|regex:/^[0-9]{10}$/',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'query' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if customer already exists
        $existingLead = Lead::where('contact_no', $request->contact_no)->first();
        $existingOpLead = OperationLead::where('contact_no', $request->contact_no)->first();

        if ($existingLead || $existingOpLead) {
            return response()->json([
                'success' => false,
                'message' => 'An account with this mobile number already exists. Please login instead.'
            ], 422);
        }

        // Create as OperationLead (since it has more fields)
        $data = $request->only([
            'customer_name',
            'contact_no',
            'location',
            'address',
        ]);

        // Generate lead_id
        $latestLead = OperationLead::orderBy('id', 'desc')->first();
        $nextNumber = $latestLead ? $latestLead->id + 1 : 1;
        $data['lead_id'] = 'CHO' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        $data['date_time'] = now();

        $operationLead = OperationLead::create($data);

        Log::info('Customer registered', [
            'operation_lead_id' => $operationLead->id,
            'contact_no' => $request->contact_no
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully! You can now login with your mobile number.',
            'redirect' => route('home')
        ]);
    }

    private function registerVendor(Request $request)
    {
        $otpService = RegistrationOtpService::for('vendor');
        if (! $otpService->isMobileVerified((string) $request->input('contact_no', ''))) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your Contact No with OTP before submitting registration.',
                'errors' => ['contact_no' => ['Mobile OTP verification is required.']],
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'contact_no' => 'required|string|max:20|regex:/^[0-9]{10}$/',
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:0|max:150',
            'gender' => 'required|in:male,female,other',
            'expected_salary' => 'nullable|numeric|min:0',
            'shift' => 'required|in:12,24,both,onetime',
            'total_experience' => 'nullable|string|max:50',
            'job_title' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'service_sub_services' => 'nullable',
            'aadhar_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'pan_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'qualification_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'required|string|max:20',
            'upi_id' => 'nullable|string|max:100',
            'bank_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'declaration_entity_accurate' => 'accepted',
            'declaration_staff_responsibility' => 'accepted',
            'declaration_agrees_terms' => 'accepted',
        ], [
            'declaration_entity_accurate.accepted' => 'Please confirm that your entity details and documents are accurate.',
            'declaration_staff_responsibility.accepted' => 'Please confirm that you accept full responsibility for your staff.',
            'declaration_agrees_terms.accepted' => 'Please confirm that you agree to Carelix\'s terms, privacy policy, and contact consent on behalf of this entity.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $serviceRow = Service::query()
            ->where('name', $request->job_title)
            ->with(['subServices' => fn ($q) => $q->where('is_active', true)])
            ->first();

        $normalizedSubServices = self::normalizeConsultationSubServices(
            self::decodeJsonArray($request->input('service_sub_services'))
        );

        if ($subErr = self::validateProviderServiceSubServices($serviceRow, $normalizedSubServices)) {
            return response()->json($subErr, 422);
        }

        // Check if vendor already exists
        $existingVendor = Vendor::where('contact_no', $request->contact_no)->first();

        if ($existingVendor) {
            return response()->json([
                'success' => false,
                'message' => 'An account with this mobile number already exists. Please login instead.'
            ], 422);
        }

        $data = $request->only([
            'customer_name',
            'contact_no',
            'name',
            'age',
            'gender',
            'expected_salary',
            'shift',
            'total_experience',
            'job_title',
            'location',
            'account_name',
            'account_number',
            'ifsc_code',
            'upi_id'
        ]);
        $latestVendor = Vendor::orderBy('id', 'desc')->first();
        $nextNumber = $latestVendor ? $latestVendor->id + 1 : 1;
        $data['lead_id'] = 'CLX-VEN-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
        $data['status'] = 'inactive'; // New vendors are inactive until approved by Operation/Operation Manager/Admin
        $data['email'] = $request->email ?? null;
        $data['service_sub_services'] = $normalizedSubServices !== [] ? $normalizedSubServices : null;

        // Build service_city_shifts JSON structure from registration form data
        if ($request->job_title && $request->location && $request->shift) {
            // Find service by name (job_title)
            $service = $serviceRow ?? Service::where('name', $request->job_title)->first();
            
            // Find location by name
            $location = Location::where('name', $request->location)->first();
            
            if ($service && $location) {
                // Create service_city_shifts structure
                $serviceCityShifts = [
                    [
                        'service_id' => $service->id,
                        'sub_services' => $normalizedSubServices,
                        'cities' => [
                            [
                                'city_id' => $location->id,
                                'shift' => $request->shift
                            ]
                        ]
                    ]
                ];
                
                // Encode to JSON and save
                $data['service_city_shifts'] = json_encode($serviceCityShifts);
                
                Log::info('Vendor service_city_shifts created', [
                    'service_id' => $service->id,
                    'location_id' => $location->id,
                    'shift' => $request->shift,
                    'service_city_shifts' => $data['service_city_shifts']
                ]);
            } else {
                Log::warning('Vendor registration: Service or Location not found', [
                    'job_title' => $request->job_title,
                    'location' => $request->location,
                    'service_found' => $service ? true : false,
                    'location_found' => $location ? true : false
                ]);
            }
        }

        // Handle file uploads
        if ($request->hasFile('aadhar_card')) {
            $data['aadhar_card'] = $request->file('aadhar_card')->store('documents/vendors', 'public');
            Log::info('Vendor Aadhar card uploaded', ['path' => $data['aadhar_card']]);
        }
        if ($request->hasFile('pan_card')) {
            $data['pan_card'] = $request->file('pan_card')->store('documents/vendors', 'public');
            Log::info('Vendor PAN card uploaded', ['path' => $data['pan_card']]);
        }
        if ($request->hasFile('qualification_certificate')) {
            $data['qualification_certificate'] = $request->file('qualification_certificate')->store('documents/vendors', 'public');
            Log::info('Vendor Qualification certificate uploaded', ['path' => $data['qualification_certificate']]);
        }
        if ($request->hasFile('bank_document')) {
            $data['bank_document'] = $request->file('bank_document')->store('documents/vendors', 'public');
            Log::info('Vendor Bank document uploaded', ['path' => $data['bank_document']]);
        }

        Log::info('Creating Vendor with data', ['data_keys' => array_keys($data), 'has_aadhar' => isset($data['aadhar_card']), 'has_pan' => isset($data['pan_card'])]);

        $vendor = Vendor::create($data);

        Log::info('Vendor registered', [
            'vendor_id' => $vendor->id,
            'contact_no' => $request->contact_no,
            'aadhar_card' => $vendor->aadhar_card,
            'pan_card' => $vendor->pan_card,
            'qualification_certificate' => $vendor->qualification_certificate,
            'bank_document' => $vendor->bank_document
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully! You can now login with your mobile number.',
            'redirect' => route('home')
        ]);
    }

    private function registerFreelancer(Request $request)
    {
        $otpService = RegistrationOtpService::for('freelancer');
        if (! $otpService->isMobileVerified((string) $request->input('contact_no', ''))) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your Contact No with OTP before submitting registration.',
                'errors' => ['contact_no' => ['Mobile OTP verification is required.']],
            ], 422);
        }

        $rules = [
            'contact_no' => 'required|string|max:20|regex:/^[0-9]{10}$/',
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:0|max:150',
            'gender' => 'required|in:male,female,other',
            'expected_salary' => 'nullable|numeric|min:0',
            'shift' => 'required|in:12,24,both,onetime',
            'total_experience' => 'nullable|string|max:50',
            'job_title' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'full_address' => 'required|string|max:2000',
            'full_address_lat' => 'nullable|numeric',
            'full_address_lng' => 'nullable|numeric',
            'radius_12hr_km' => 'nullable|numeric|min:0.1|max:5000',
            'radius_24hr_km' => 'nullable|numeric|min:0.1|max:5000',
            'radius_onetime_km' => 'nullable|numeric|min:0.1|max:5000',
            'service_sub_services' => 'nullable',
            'aadhar_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'pan_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'qualification_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'bank_payment_method' => 'required|in:account,upi',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'upi_id' => 'nullable|string|max:100',
            'bank_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'declaration_understands_platform' => 'accepted',
            'declaration_agrees_terms' => 'accepted',
        ];

        if (JobRequest::isNurseJobTitle($request->input('job_title'))) {
            $rules['declaration_details_accurate'] = 'accepted';
        }

        $shift = (string) $request->input('shift');
        if (in_array($shift, ['12', 'both'], true)) {
            $rules['radius_12hr_km'] = 'required|numeric|min:0.1|max:5000';
        }
        if (in_array($shift, ['24', 'both'], true)) {
            $rules['radius_24hr_km'] = 'required|numeric|min:0.1|max:5000';
        }
        if ($shift === 'onetime') {
            $rules['radius_onetime_km'] = 'required|numeric|min:0.1|max:5000';
        }

        $bankMethod = (string) $request->input('bank_payment_method', 'account');
        if ($bankMethod === 'upi') {
            $rules['upi_id'] = 'required|string|max:100';
        } else {
            $rules['account_name'] = 'required|string|max:255';
            $rules['account_number'] = 'required|string|max:50';
            $rules['ifsc_code'] = 'required|string|max:20';
            $rules['bank_document'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:20480';
        }

        $validator = Validator::make($request->all(), $rules, [
            'declaration_details_accurate.accepted' => 'Please confirm that your details and documents are accurate.',
            'declaration_understands_platform.accepted' => 'Please confirm that you understand how Carelix works.',
            'declaration_agrees_terms.accepted' => 'Please confirm that you agree to Carelix\'s terms, privacy policy, and contact consent.',
            'full_address.required' => 'Please enter your Full Address (or use Fetch by GPS).',
            'radius_12hr_km.required' => 'Please enter work radius (km) for 12hr.',
            'radius_24hr_km.required' => 'Please enter work radius (km) for 24hr.',
            'radius_onetime_km.required' => 'Please enter work radius (km) for One-time.',
            'upi_id.required' => 'Please enter your UPI ID.',
            'bank_document.required' => 'Please upload Bank Document (Cancelled Cheque).',
        ]);

        if ($validator->fails()) {
            Log::error('Freelancer registration validation failed', ['errors' => $validator->errors()->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $serviceRow = Service::query()
            ->where('name', $request->job_title)
            ->with(['subServices' => fn ($q) => $q->where('is_active', true)])
            ->first();

        $normalizedSubServices = self::normalizeConsultationSubServices(
            self::decodeJsonArray($request->input('service_sub_services'))
        );

        if ($subErr = self::validateProviderServiceSubServices($serviceRow, $normalizedSubServices)) {
            return response()->json($subErr, 422);
        }

        // Check if freelancer already exists
        $existingFreelancer = JobRequest::where('mobile', $request->contact_no)
            ->orWhere('contact_no', $request->contact_no)
            ->first();

        if ($existingFreelancer) {
            return response()->json([
                'success' => false,
                'message' => 'An account with this mobile number already exists. Please login instead.'
            ], 422);
        }

        if (DoctorRequest::where('mobile', $request->contact_no)->orWhere('contact_no', $request->contact_no)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This mobile number is already registered as a doctor. Please use a different number or login with that account.'
            ], 422);
        }

        // Log file upload status
        Log::info('File upload check', [
            'has_aadhar_card' => $request->hasFile('aadhar_card'),
            'has_pan_card' => $request->hasFile('pan_card'),
            'has_qualification_certificate' => $request->hasFile('qualification_certificate'),
            'has_bank_document' => $request->hasFile('bank_document')
        ]);

        $data = $request->only([
            'contact_no',
            'name',
            'job_title',
            'location',
            'full_address',
            'shift',
            'expected_salary',
            'total_experience',
            'account_name',
            'account_number',
            'ifsc_code',
            'upi_id'
        ]);

        // customer_name column kept in sync with Name (Customer Name field removed from form).
        $data['customer_name'] = trim((string) $request->input('name'));
        $data['full_address'] = trim((string) $request->input('full_address'));
        $data['full_address_lat'] = $request->filled('full_address_lat') ? $request->input('full_address_lat') : null;
        $data['full_address_lng'] = $request->filled('full_address_lng') ? $request->input('full_address_lng') : null;

        $shiftVal = (string) $request->input('shift');
        $data['radius_12hr_km'] = in_array($shiftVal, ['12', 'both'], true) && $request->filled('radius_12hr_km')
            ? $request->input('radius_12hr_km')
            : null;
        $data['radius_24hr_km'] = in_array($shiftVal, ['24', 'both'], true) && $request->filled('radius_24hr_km')
            ? $request->input('radius_24hr_km')
            : null;
        $data['radius_onetime_km'] = $shiftVal === 'onetime' && $request->filled('radius_onetime_km')
            ? $request->input('radius_onetime_km')
            : null;

        if ($bankMethod === 'upi') {
            $data['account_name'] = null;
            $data['account_number'] = null;
            $data['ifsc_code'] = null;
            $data['bank_document'] = null;
            $data['upi_id'] = trim((string) $request->input('upi_id'));
        } else {
            $data['upi_id'] = null;
        }

        // Set mobile from contact_no (for login purposes)
        $data['mobile'] = $request->contact_no;
        
        // Set city same as location if location is provided
        if ($request->location) {
            $data['city'] = $request->location;
        }
        
        // Combine age and gender (for backward compatibility with existing age field format)
        $data['age'] = $request->age . '|' . $request->gender;
        
        // Also store gender separately if column exists
        if (Schema::hasColumn('job_requests', 'gender')) {
            $data['gender'] = $request->gender;
        }
        
        $data['date_time'] = now();
        $data['status'] = 'inactive'; // New freelancers are inactive until approved by Operation/Operation Manager/Admin
        $data['service_sub_services'] = $normalizedSubServices !== [] ? $normalizedSubServices : null;

        // Generate lead_id automatically (format: JR00000001, JR00000002, etc.)
        // Use the latest JobRequest id + 1 to ensure unique sequential numbering
        $latestJobRequest = JobRequest::orderBy('id', 'desc')->first();
        $nextNumber = $latestJobRequest ? $latestJobRequest->id + 1 : 1;
        $data['lead_id'] = 'CLX-FRL-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

        // Handle file uploads
        if ($request->hasFile('aadhar_card')) {
            $data['aadhar_card'] = $request->file('aadhar_card')->store('documents/freelancers', 'public');
            Log::info('Aadhar card uploaded', ['path' => $data['aadhar_card']]);
        }
        if ($request->hasFile('pan_card')) {
            $data['pan_card'] = $request->file('pan_card')->store('documents/freelancers', 'public');
            Log::info('PAN card uploaded', ['path' => $data['pan_card']]);
        }
        if ($request->hasFile('qualification_certificate')) {
            $data['qualification_certificate'] = $request->file('qualification_certificate')->store('documents/freelancers', 'public');
            Log::info('Qualification certificate uploaded', ['path' => $data['qualification_certificate']]);
        }
        if ($bankMethod !== 'upi' && $request->hasFile('bank_document')) {
            $data['bank_document'] = $request->file('bank_document')->store('documents/freelancers', 'public');
            Log::info('Bank document uploaded', ['path' => $data['bank_document']]);
        }

        Log::info('Creating JobRequest with data', ['data_keys' => array_keys($data), 'has_aadhar' => isset($data['aadhar_card']), 'has_pan' => isset($data['pan_card'])]);

        $jobRequest = JobRequest::create($data);

        Log::info('Freelancer registered', [
            'job_request_id' => $jobRequest->id,
            'mobile' => $request->contact_no,
            'aadhar_card' => $jobRequest->aadhar_card,
            'pan_card' => $jobRequest->pan_card,
            'qualification_certificate' => $jobRequest->qualification_certificate,
            'bank_document' => $jobRequest->bank_document
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully! You can now login with your mobile number.',
            'redirect' => route('home')
        ]);
    }

    public function sendDoctorMobileOtp(Request $request)
    {
        return $this->sendRegistrationMobileOtp($request, 'doctor');
    }

    public function verifyDoctorMobileOtp(Request $request)
    {
        return $this->verifyRegistrationMobileOtp($request, 'doctor');
    }

    public function sendVendorMobileOtp(Request $request)
    {
        return $this->sendRegistrationMobileOtp($request, 'vendor');
    }

    public function verifyVendorMobileOtp(Request $request)
    {
        return $this->verifyRegistrationMobileOtp($request, 'vendor');
    }

    public function sendFreelancerMobileOtp(Request $request)
    {
        return $this->sendRegistrationMobileOtp($request, 'freelancer');
    }

    public function verifyFreelancerMobileOtp(Request $request)
    {
        return $this->verifyRegistrationMobileOtp($request, 'freelancer');
    }

    /** Debug: fetch OTP + timestamp + IP (only when APP_DEBUG=true). */
    public function fetchDoctorMobileOtpMeta(Request $request, DoctorRegistrationOtpService $otpService)
    {
        if (! config('app.debug')) {
            return response()->json(['success' => false, 'message' => 'Not available.'], 403);
        }

        $mobile = (string) $request->query('mobile', '');
        $meta = $otpService->getOtpMeta($mobile);

        if ($meta === null) {
            return response()->json([
                'success' => false,
                'message' => 'No OTP found for this number (or invalid number).',
            ], 404);
        }

        return response()->json(['success' => true, 'meta' => $meta]);
    }

    private function sendRegistrationMobileOtp(Request $request, string $type)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $result = RegistrationOtpService::for($type)->sendOtp(
            (string) $request->input('mobile'),
            (string) $request->ip()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function verifyRegistrationMobileOtp(Request $request, string $type)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|max:20',
            'otp' => 'required|string|regex:/^[0-9]{6}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $result = RegistrationOtpService::for($type)->verifyOtp(
            (string) $request->input('mobile'),
            (string) $request->input('otp'),
            (string) $request->ip()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function registerDoctor(Request $request)
    {
        $otpService = RegistrationOtpService::for('doctor');
        if (! $otpService->isMobileVerified((string) $request->input('contact_no', ''))) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your mobile number with OTP before submitting registration.',
                'errors' => ['contact_no' => ['Mobile OTP verification is required.']],
            ], 422);
        }

        $allowedModes = ['online', 'home_visit', 'clinic_visit'];
        $modes = $request->input('consultation_modes', []);
        if (!is_array($modes)) {
            $modes = [];
        }
        $modes = array_values(array_unique(array_intersect($allowedModes, $modes)));

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'contact_no' => 'required|string|max:20|regex:/^[0-9]{10}$/',
            'gender' => 'required|in:male,female,other',
            'city' => ['required', 'string', 'max:255', Rule::exists('locations', 'name')],
            'selfie' => 'required|file|mimes:jpeg,jpg,png,webp|max:10240',
            'job_title' => [
                'required',
                'string',
                'max:255',
                Rule::exists('doctor_consultation_services', 'name')->where('is_active', true),
            ],
            'consultation_modes' => 'required|array|min:1',
            'consultation_modes.*' => Rule::in($allowedModes),
            'permanent_address' => 'required|string|max:2000',
            'current_address' => 'required|string|max:2000',
            'current_same_as_permanent' => 'nullable|boolean',
            'aadhar_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'pan_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'qualification_certificate' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'account_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'required|string|max:20',
            'bank_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'declaration_details_accurate' => 'accepted',
            'declaration_understands_platform' => 'accepted',
            'declaration_agrees_terms' => 'accepted',
            'fluent_languages' => 'required|array|min:1',
            'fluent_languages.*' => Rule::in(array_keys(config('doctor_registration.fluent_languages', []))),
            'about_text' => 'required|string|min:20|max:8000',
            'education_history' => 'required',
            'experience_history' => 'required',
            'specializations' => 'nullable',
            'consultation_sub_services' => 'nullable',
            'consultation_pricing' => 'required',
        ];

        // Charges are captured per service/sub-service in consultation_pricing (admin-locked or doctor-entered).
        $rules['online_charges'] = 'nullable|numeric|min:0';
        if (in_array('home_visit', $modes, true)) {
            $rules['home_visit_charges'] = 'nullable|numeric|min:0';
            $rules['coverage_radius_km'] = 'required|numeric|min:0.1|max:5000';
            $rules['base_location_address'] = 'required|string|max:2000';
            $rules['base_location_lat'] = 'nullable|numeric';
            $rules['base_location_lng'] = 'nullable|numeric';
        } else {
            $rules['home_visit_charges'] = 'nullable|numeric|min:0';
            $rules['coverage_radius_km'] = 'nullable|numeric';
            $rules['base_location_address'] = 'nullable|string|max:2000';
            $rules['base_location_lat'] = 'nullable|numeric';
            $rules['base_location_lng'] = 'nullable|numeric';
        }
        if (in_array('clinic_visit', $modes, true)) {
            $rules['clinic_consultation_charges'] = 'nullable|numeric|min:0';
            $rules['clinic_name'] = 'required|string|max:255';
            $rules['clinic_address'] = 'required|string|max:2000';
            $rules['clinic_lat'] = 'nullable|numeric';
            $rules['clinic_lng'] = 'nullable|numeric';
        } else {
            $rules['clinic_consultation_charges'] = 'nullable|numeric|min:0';
            $rules['clinic_name'] = 'nullable|string|max:255';
            $rules['clinic_address'] = 'nullable|string|max:2000';
            $rules['clinic_lat'] = 'nullable|numeric';
            $rules['clinic_lng'] = 'nullable|numeric';
        }

        $validator = Validator::make($request->all(), $rules, [
            'declaration_details_accurate.accepted' => 'Please confirm that your details and documents are accurate.',
            'declaration_understands_platform.accepted' => 'Please confirm that you understand how Carelix works.',
            'declaration_agrees_terms.accepted' => 'Please confirm that you agree to Carelix\'s terms, privacy policy, and contact consent.',
            'selfie.required' => 'Please upload a selfie.',
        ]);

        if ($validator->fails()) {
            Log::error('Doctor registration validation failed', ['errors' => $validator->errors()->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (in_array('home_visit', $modes, true) && (! $request->filled('base_location_lat') || ! $request->filled('base_location_lng'))) {
            return response()->json([
                'success' => false,
                'message' => 'Please search and select your home visit base location (pick an address from suggestions).',
                'errors' => ['base_location_address' => ['Location coordinates are required. Use address search and pick a suggestion.']],
            ], 422);
        }
        if (in_array('clinic_visit', $modes, true) && (! $request->filled('clinic_lat') || ! $request->filled('clinic_lng'))) {
            return response()->json([
                'success' => false,
                'message' => 'Please search and select your clinic location (pick an address from suggestions).',
                'errors' => ['clinic_address' => ['Location coordinates are required. Use address search and pick a suggestion.']],
            ], 422);
        }

        $educationHistory = self::decodeJsonArray($request->input('education_history'));
        $experienceHistory = self::decodeJsonArray($request->input('experience_history'));
        $specializations = self::decodeJsonArray($request->input('specializations'));
        $consultationSubServices = self::decodeJsonArray($request->input('consultation_sub_services'));
        $consultationPricingRaw = self::decodeJsonArray($request->input('consultation_pricing'));

        $profileValidator = Validator::make(
            [
                'education_history' => $educationHistory,
                'experience_history' => $experienceHistory,
                'specializations' => $specializations,
                'consultation_sub_services' => $consultationSubServices,
            ],
            [
                'education_history' => 'required|array|min:1|max:20',
                'education_history.*.degree' => 'required|string|max:500',
                'education_history.*.institution' => 'required|string|max:500',
                'education_history.*.year_completed' => 'nullable|string|max:32',
                'experience_history' => 'required|array|min:1|max:30',
                'experience_history.*.title' => 'required|string|max:500',
                'experience_history.*.organization' => 'required|string|max:500',
                'experience_history.*.from_year' => 'required|string|max:32',
                'experience_history.*.to_year' => 'nullable|string|max:32',
                'experience_history.*.details' => 'nullable|string|max:2000',
                'specializations' => 'nullable|array|max:50',
                'specializations.*' => 'string|max:255',
                'consultation_sub_services' => 'nullable|array|max:30',
                'consultation_sub_services.*.sub_service_id' => 'nullable|integer',
                'consultation_sub_services.*.sub_service_name' => 'nullable|string|max:255',
                'consultation_sub_services.*.tags' => 'nullable|array|max:50',
                'consultation_sub_services.*.tags.*' => 'string|max:255',
            ],
            [
                'education_history.required' => 'Add at least one education entry (latest first).',
                'experience_history.required' => 'Add at least one experience entry (latest first).',
            ]
        );

        if ($profileValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $profileValidator->errors(),
            ], 422);
        }

        $serviceRow = DoctorConsultationService::query()
            ->where('is_active', true)
            ->where('name', $request->job_title)
            ->with(['subServices' => fn ($q) => $q->where('is_active', true)])
            ->first();

        $tagValidation = self::validateDoctorConsultationTags(
            $serviceRow,
            $specializations,
            $consultationSubServices
        );
        if ($tagValidation !== null) {
            return response()->json($tagValidation, 422);
        }

        $normalizedSubServices = self::normalizeConsultationSubServices($consultationSubServices);
        $specializations = self::flattenTagsFromSubServices($normalizedSubServices, $specializations);

        $location = Location::query()->where('name', trim((string) $request->city))->first();
        if (! $location) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid city selected.',
                'errors' => ['city' => ['Invalid city.']],
            ], 422);
        }

        $pricingNormalized = self::normalizeDoctorConsultationPricing(
            $consultationPricingRaw,
            (int) $location->id,
            (int) ($serviceRow?->id ?? 0),
            $normalizedSubServices,
            $modes
        );
        if (is_string($pricingNormalized['error'] ?? null) && ($pricingNormalized['error'] ?? '') !== '') {
            return response()->json([
                'success' => false,
                'message' => (string) $pricingNormalized['error'],
                'errors' => ['consultation_pricing' => [(string) $pricingNormalized['error']]],
            ], 422);
        }
        $pricingOut = is_array($pricingNormalized['pricing'] ?? null) ? $pricingNormalized['pricing'] : [];

        self::sortEducationHistoryNewestFirst($educationHistory);
        self::sortExperienceHistoryNewestFirst($experienceHistory);

        $norm = static function ($s) {
            return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $s)));
        };
        if ($norm($request->account_name) !== $norm($request->name)) {
            return response()->json([
                'success' => false,
                'message' => 'Account holder name must match your full name (same spelling).',
                'errors' => ['account_name' => ['Account holder name must match doctor name.']],
            ], 422);
        }

        $existing = DoctorRequest::where('mobile', $request->contact_no)
            ->orWhere('contact_no', $request->contact_no)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'An account with this mobile number already exists. Please login instead.',
            ], 422);
        }

        $existingFreelancer = JobRequest::where('mobile', $request->contact_no)
            ->orWhere('contact_no', $request->contact_no)
            ->first();
        if ($existingFreelancer) {
            return response()->json([
                'success' => false,
                'message' => 'This mobile number is already registered as a freelancer. Please use a different number or login with that account.',
            ], 422);
        }

        $currentAddress = $request->boolean('current_same_as_permanent')
            ? $request->permanent_address
            : $request->current_address;

        $data = [
            'customer_name' => $request->name,
            'contact_no' => $request->contact_no,
            'mobile' => $request->contact_no,
            'name' => $request->name,
            'email' => strtolower(trim((string) $request->email)),
            'gender' => strtolower((string) $request->gender),
            'city' => trim((string) $request->city),
            'location' => trim((string) $request->city),
            'job_title' => $request->job_title,
            'consultation_modes' => $modes,
            'consultation_pricing' => $pricingOut !== [] ? $pricingOut : null,
            'online_charges' => in_array('online', $modes, true) ? ($pricingNormalized['legacy']['online_charges'] ?? null) : null,
            'home_visit_charges' => in_array('home_visit', $modes, true) ? ($pricingNormalized['legacy']['home_visit_charges'] ?? null) : null,
            'coverage_radius_km' => in_array('home_visit', $modes, true) ? $request->coverage_radius_km : null,
            'base_location_address' => in_array('home_visit', $modes, true) ? $request->base_location_address : null,
            'base_location_lat' => in_array('home_visit', $modes, true) ? ($request->filled('base_location_lat') ? $request->base_location_lat : null) : null,
            'base_location_lng' => in_array('home_visit', $modes, true) ? ($request->filled('base_location_lng') ? $request->base_location_lng : null) : null,
            'clinic_consultation_charges' => in_array('clinic_visit', $modes, true) ? ($pricingNormalized['legacy']['clinic_consultation_charges'] ?? null) : null,
            'clinic_name' => in_array('clinic_visit', $modes, true) ? $request->clinic_name : null,
            'clinic_address' => in_array('clinic_visit', $modes, true) ? $request->clinic_address : null,
            'clinic_lat' => in_array('clinic_visit', $modes, true) ? ($request->filled('clinic_lat') ? $request->clinic_lat : null) : null,
            'clinic_lng' => in_array('clinic_visit', $modes, true) ? ($request->filled('clinic_lng') ? $request->clinic_lng : null) : null,
            'permanent_address' => $request->permanent_address,
            'current_address' => $currentAddress,
            'current_same_as_permanent' => $request->boolean('current_same_as_permanent'),
            'fluent_languages' => array_values(array_intersect(
                array_keys(config('doctor_registration.fluent_languages', [])),
                $request->input('fluent_languages', [])
            )),
            'about_text' => $request->input('about_text'),
            'education_history' => $educationHistory,
            'experience_history' => $experienceHistory,
            'specializations' => array_values(array_unique(array_filter(
                array_map('strval', $specializations),
                static fn ($s) => trim($s) !== ''
            ))),
            'consultation_sub_services' => $normalizedSubServices !== [] ? $normalizedSubServices : null,
            'account_name' => $request->account_name,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'ifsc_code' => $request->ifsc_code,
            'date_time' => now(),
            'approval_status' => 'pending',
        ];

        $latest = DoctorRequest::orderBy('id', 'desc')->first();
        $nextNumber = $latest ? $latest->id + 1 : 1;
        $data['lead_id'] = 'CLX-DOC-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

        if ($request->hasFile('selfie')) {
            $data['profile_image_upload'] = $request->file('selfie')->store('documents/doctors/selfies', 'public');
            $data['profile_image'] = null;
            $data['profile_image_status'] = 'pending_review';
        }
        if ($request->hasFile('aadhar_card')) {
            $data['aadhar_card'] = $request->file('aadhar_card')->store('documents/doctors', 'public');
        }
        if ($request->hasFile('pan_card')) {
            $data['pan_card'] = $request->file('pan_card')->store('documents/doctors', 'public');
        }
        if ($request->hasFile('qualification_certificate')) {
            $data['qualification_certificate'] = $request->file('qualification_certificate')->store('documents/doctors', 'public');
        }
        if ($request->hasFile('bank_document')) {
            $data['bank_document'] = $request->file('bank_document')->store('documents/doctors', 'public');
        }

        DoctorRequest::create($data);

        Log::info('Doctor registered', ['mobile' => $request->contact_no]);

        return response()->json([
            'success' => true,
            'message' => 'Registration submitted. Your profile photo will go live after admin generates and approves your Carelix coat image.',
            'redirect' => route('home'),
        ]);
    }

    /** @return array<int, mixed> */
    private static function decodeJsonArray(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /** @param array<int, array<string, mixed>> $rows */
    private static function sortEducationHistoryNewestFirst(array &$rows): void
    {
        usort($rows, function ($a, $b) {
            $ya = (int) preg_replace('/\D/', '', (string) ($a['year_completed'] ?? ''));
            $yb = (int) preg_replace('/\D/', '', (string) ($b['year_completed'] ?? ''));

            return $yb <=> $ya;
        });
    }

    /** @param array<int, array<string, mixed>> $rows */
    private static function sortExperienceHistoryNewestFirst(array &$rows): void
    {
        usort($rows, function ($a, $b) {
            $ya = (int) preg_replace('/\D/', '', (string) ($a['from_year'] ?? ''));
            $yb = (int) preg_replace('/\D/', '', (string) ($b['from_year'] ?? ''));

            return $yb <=> $ya;
        });
    }

    /**
     * @param  array<int, mixed>  $specializations
     * @param  array<int, mixed>  $consultationSubServices
     * @return array{success: false, message: string, errors: array<string, array<int, string>>}|null
     */
    private static function validateDoctorConsultationTags(
        ?DoctorConsultationService $serviceRow,
        array $specializations,
        array $consultationSubServices
    ): ?array {
        if ($serviceRow === null) {
            return null;
        }

        $activeSubs = $serviceRow->subServices->filter(fn ($s) => (bool) $s->is_active);
        if ($activeSubs->isNotEmpty()) {
            $normalized = self::normalizeConsultationSubServices($consultationSubServices);
            if ($normalized === []) {
                return [
                    'success' => false,
                    'message' => 'Select at least one sub-service for your consultation service.',
                    'errors' => ['consultation_sub_services' => ['Choose one or more sub-services.']],
                ];
            }

            $subById = $activeSubs->keyBy('id');
            foreach ($normalized as $entry) {
                $subId = (int) ($entry['sub_service_id'] ?? 0);
                $sub = $subById->get($subId);
                if ($sub === null) {
                    return [
                        'success' => false,
                        'message' => 'Invalid sub-service for the selected consultation service.',
                        'errors' => ['consultation_sub_services' => ['One or more sub-services are not valid.']],
                    ];
                }
                $allowed = is_array($sub->specialization_options)
                    ? array_values(array_filter(array_map('strval', $sub->specialization_options)))
                    : [];
                $tags = $entry['tags'] ?? [];
                if ($allowed !== [] && count($tags) === 0) {
                    return [
                        'success' => false,
                        'message' => 'Har sub-service ke liye kam se kam ek tag select karein.',
                        'errors' => ['consultation_sub_services' => ['Tags required for: '.$sub->name]],
                    ];
                }
                foreach ($tags as $tag) {
                    if ($allowed !== [] && ! in_array((string) $tag, $allowed, true)) {
                        return [
                            'success' => false,
                            'message' => 'Invalid tag for sub-service '.$sub->name.'.',
                            'errors' => ['consultation_sub_services' => ['Tag not allowed: '.$tag]],
                        ];
                    }
                }
            }

            return null;
        }

        $allowedSpecs = is_array($serviceRow->specialization_options)
            ? array_values(array_filter(array_map('strval', $serviceRow->specialization_options)))
            : [];
        if ($allowedSpecs === []) {
            return null;
        }
        if (count($specializations) === 0) {
            return [
                'success' => false,
                'message' => 'Select at least one tag for your consultation service.',
                'errors' => ['specializations' => ['Choose one or more tags listed for this service.']],
            ];
        }
        foreach ($specializations as $spec) {
            if (! in_array((string) $spec, $allowedSpecs, true)) {
                return [
                    'success' => false,
                    'message' => 'Invalid tag for the selected consultation service.',
                    'errors' => ['specializations' => ['One or more tags are not allowed for this service.']],
                ];
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return list<array{sub_service_id: int, sub_service_name: string, tags: list<string>}>
     */
    private static function normalizeConsultationSubServices(array $raw): array
    {
        $out = [];
        $seen = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = (int) ($row['sub_service_id'] ?? 0);
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $name = trim((string) ($row['sub_service_name'] ?? ''));
            $tags = [];
            foreach ($row['tags'] ?? [] as $tag) {
                $t = trim((string) $tag);
                if ($t !== '') {
                    $tags[] = $t;
                }
            }
            $tags = array_values(array_unique($tags));
            $seen[$id] = true;
            $out[] = [
                'sub_service_id' => $id,
                'sub_service_name' => $name,
                'tags' => $tags,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{sub_service_id: int, sub_service_name: string, tags: list<string>}>  $normalizedSubServices
     * @return array{success: false, message: string, errors: array<string, array<int, string>>}|null
     */
    private static function validateProviderServiceSubServices(?Service $serviceRow, array $normalizedSubServices): ?array
    {
        if ($serviceRow === null) {
            return [
                'success' => false,
                'message' => 'Selected service is not valid.',
                'errors' => ['job_title' => ['Invalid job title.']],
            ];
        }

        $activeSubs = $serviceRow->subServices->filter(fn ($s) => (bool) $s->is_active);

        if ($activeSubs->isNotEmpty()) {
            if ($normalizedSubServices === []) {
                return [
                    'success' => false,
                    'message' => 'Select at least one sub-service.',
                    'errors' => ['service_sub_services' => ['Choose one or more sub-services.']],
                ];
            }

            $subById = $activeSubs->keyBy('id');
            foreach ($normalizedSubServices as $entry) {
                $subId = (int) ($entry['sub_service_id'] ?? 0);
                $sub = $subById->get($subId);
                if ($sub === null) {
                    return [
                        'success' => false,
                        'message' => 'Invalid sub-service for the selected service.',
                        'errors' => ['service_sub_services' => ['One or more sub-services are not valid.']],
                    ];
                }
                $allowed = is_array($sub->specialization_options)
                    ? array_values(array_filter(array_map('strval', $sub->specialization_options)))
                    : [];
                $tags = $entry['tags'] ?? [];
                if ($allowed !== [] && count($tags) === 0) {
                    return [
                        'success' => false,
                        'message' => 'Har sub-service ke liye kam se kam ek tag select karein.',
                        'errors' => ['service_sub_services' => ['Tags required for: '.$sub->name]],
                    ];
                }
                foreach ($tags as $tag) {
                    if ($allowed !== [] && ! in_array((string) $tag, $allowed, true)) {
                        return [
                            'success' => false,
                            'message' => 'Invalid tag for sub-service '.$sub->name.'.',
                            'errors' => ['service_sub_services' => ['Tag not allowed: '.$tag]],
                        ];
                    }
                }
            }

            return null;
        }

        $allowedSpecs = is_array($serviceRow->specialization_options)
            ? array_values(array_filter(array_map('strval', $serviceRow->specialization_options)))
            : [];

        if ($allowedSpecs !== []) {
            $tags = $normalizedSubServices[0]['tags'] ?? [];
            if (count($tags) === 0) {
                return [
                    'success' => false,
                    'message' => 'Is service ke liye kam se kam ek tag select karein.',
                    'errors' => ['service_sub_services' => ['Choose at least one tag.']],
                ];
            }
            foreach ($tags as $tag) {
                if (! in_array((string) $tag, $allowedSpecs, true)) {
                    return [
                        'success' => false,
                        'message' => 'Invalid tag selected.',
                        'errors' => ['service_sub_services' => ['Tag not allowed: '.$tag]],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @param  list<array{sub_service_id: int, sub_service_name: string, tags: list<string>}>  $normalizedSubServices
     * @param  array<int, mixed>  $fallbackSpecs
     * @return list<string>
     */
    private static function flattenTagsFromSubServices(array $normalizedSubServices, array $fallbackSpecs): array
    {
        if ($normalizedSubServices !== []) {
            $flat = [];
            foreach ($normalizedSubServices as $entry) {
                foreach ($entry['tags'] as $tag) {
                    $flat[] = $tag;
                }
            }

            return array_values(array_unique($flat));
        }

        return array_values(array_unique(array_filter(
            array_map('strval', $fallbackSpecs),
            static fn ($s) => trim($s) !== ''
        )));
    }

    /**
     * @param  array<int, mixed>  $rawPricing
     * @param  list<array{sub_service_id: int, sub_service_name: string, tags: list<string>}>  $normalizedSubServices
     * @param  list<string>  $modes
     * @return array{pricing: list<array<string, mixed>>, legacy: array<string, mixed>, error?: string}
     */
    private static function normalizeDoctorConsultationPricing(
        array $rawPricing,
        int $locationId,
        int $serviceId,
        array $normalizedSubServices,
        array $modes
    ): array {
        if ($locationId <= 0 || $serviceId <= 0) {
            return ['pricing' => [], 'legacy' => [], 'error' => 'Consultation pricing could not be resolved. Please re-select your city and consultation service.'];
        }

        $selectedSubIds = [];
        $subNameById = [];
        foreach ($normalizedSubServices as $row) {
            $sid = (int) ($row['sub_service_id'] ?? 0);
            if ($sid > 0) {
                $selectedSubIds[] = $sid;
                $subNameById[$sid] = (string) ($row['sub_service_name'] ?? '');
            }
        }
        $selectedSubIds = array_values(array_unique($selectedSubIds));
        $hasSubs = count($selectedSubIds) > 0;

        // Build doctor-entered lookup from raw pricing payload.
        $rawBySubMode = [];
        foreach ($rawPricing as $row) {
            if (! is_array($row)) {
                continue;
            }
            $sid = (int) ($row['sub_service_id'] ?? 0);
            $m = $row['modes'] ?? [];
            if (! is_array($m)) {
                continue;
            }
            foreach ($m as $modeKey => $modeRow) {
                if (! is_array($modeRow)) {
                    continue;
                }
                $price = $modeRow['doctor_price'] ?? null;
                if ($price === '' || $price === null) {
                    continue;
                }
                if (! is_numeric($price)) {
                    continue;
                }
                $rawBySubMode[$sid] = $rawBySubMode[$sid] ?? [];
                $rawBySubMode[$sid][(string) $modeKey] = (float) $price;
            }
        }

        // Fetch admin pricing for this location/service and relevant subs (or service-level when no sub-services).
        $adminRows = LocationDoctorConsultationPrice::query()
            ->where('location_id', $locationId)
            ->where('doctor_consultation_service_id', $serviceId)
            ->where('is_active', true)
            ->when($hasSubs, function ($q) use ($selectedSubIds) {
                $q->whereIn('doctor_consultation_service_sub_service_id', $selectedSubIds);
            }, function ($q) {
                $q->whereNull('doctor_consultation_service_sub_service_id');
            })
            ->get();

        $adminBySubMode = [];
        foreach ($adminRows as $row) {
            $sid = $row->doctor_consultation_service_sub_service_id ? (int) $row->doctor_consultation_service_sub_service_id : 0;
            $mode = (string) $row->consultation_mode;
            $adminBySubMode[$sid] = $adminBySubMode[$sid] ?? [];
            $adminBySubMode[$sid][$mode] = [
                'website_price' => $row->website_price,
                'doctor_max_price' => $row->doctor_max_price,
            ];
        }

        $out = [];
        $legacy = [
            'online_charges' => null,
            'home_visit_charges' => null,
            'clinic_consultation_charges' => null,
        ];

        $items = $hasSubs ? $selectedSubIds : [0];
        foreach ($items as $sid) {
            $row = [
                'location_id' => $locationId,
                'service_id' => $serviceId,
                'sub_service_id' => $sid,
                'sub_service_name' => $sid > 0 ? ($subNameById[$sid] ?? null) : null,
                'modes' => [],
            ];

            foreach ($modes as $modeKey) {
                $modeKey = (string) $modeKey;
                $admin = $adminBySubMode[$sid][$modeKey] ?? null;
                $locked = $admin !== null && ($admin['doctor_max_price'] ?? null) !== null && $admin['doctor_max_price'] !== '';

                $doctorPrice = null;
                if ($locked) {
                    $doctorPrice = (float) $admin['doctor_max_price'];
                } else {
                    $doctorPrice = $rawBySubMode[$sid][$modeKey] ?? null;
                }

                if (! $locked && ($doctorPrice === null || $doctorPrice === '')) {
                    $label = LocationDoctorConsultationPrice::modeLabel($modeKey);
                    $itemLabel = $sid > 0 ? ($subNameById[$sid] ?? 'Sub-service') : 'Service';

                    return [
                        'pricing' => [],
                        'legacy' => [],
                        'error' => "Please enter charges for {$label} — {$itemLabel}.",
                    ];
                }

                if ($admin !== null && ($admin['doctor_max_price'] ?? null) !== null && $doctorPrice !== null) {
                    $max = (float) $admin['doctor_max_price'];
                    if ($doctorPrice > $max) {
                        $label = LocationDoctorConsultationPrice::modeLabel($modeKey);
                        $itemLabel = $sid > 0 ? ($subNameById[$sid] ?? 'Sub-service') : 'Service';

                        return [
                            'pricing' => [],
                            'legacy' => [],
                            'error' => "{$label} charges for {$itemLabel} cannot be higher than allowed max (₹{$max}).",
                        ];
                    }
                }

                $row['modes'][$modeKey] = [
                    'doctor_price' => $doctorPrice !== null ? (float) $doctorPrice : null,
                    'website_price' => $admin['website_price'] ?? null,
                    'doctor_max_price' => $admin['doctor_max_price'] ?? null,
                    'locked' => (bool) $locked,
                ];

                // legacy values from first item (best-effort)
                if ($legacy['online_charges'] === null && $modeKey === 'online' && $doctorPrice !== null) {
                    $legacy['online_charges'] = (float) $doctorPrice;
                }
                if ($legacy['home_visit_charges'] === null && $modeKey === 'home_visit' && $doctorPrice !== null) {
                    $legacy['home_visit_charges'] = (float) $doctorPrice;
                }
                if ($legacy['clinic_consultation_charges'] === null && $modeKey === 'clinic_visit' && $doctorPrice !== null) {
                    $legacy['clinic_consultation_charges'] = (float) $doctorPrice;
                }
            }

            $out[] = $row;
        }

        return [
            'pricing' => $out,
            'legacy' => $legacy,
        ];
    }
}
