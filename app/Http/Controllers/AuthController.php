<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\JobRequest;
use App\Models\DoctorRequest;
use App\Models\B2BReferenceUser;
use App\Models\DoctorReferralUser;
use App\Models\B2BUser;
use App\Models\Vendor;
use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\ConsultationWebsiteBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\WabaLoginOtpService;

class AuthController extends Controller
{
    /** Fixed OTP login for demo / QA numbers (no WhatsApp send). */
    private const DEMO_OTP = '999999';

    private const DEMO_OTP_MOBILES = [
        '7754966128',
        '9999999999',
        '8888888888',
        '7777777777',
    ];

    private function usesDemoFixedOtp(string $mobile): bool
    {
        return in_array($mobile, self::DEMO_OTP_MOBILES, true);
    }

    private function normalizeMobileToTenDigits($mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);
        if (!is_string($digits) || $digits === '' || strlen($digits) < 10) {
            return null;
        }

        return substr($digits, -10);
    }

    private function applyMobileMatch($query, string $column, string $mobile): void
    {
        // Supports 10-digit, 91+10, +91+10 and formatted stored values.
        $query->where(function ($q) use ($column, $mobile) {
            $q->where($column, $mobile)
                ->orWhere($column, '91'.$mobile)
                ->orWhere($column, '+91'.$mobile)
                ->orWhereRaw(
                    "RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE($column,''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), 10) = ?",
                    [$mobile]
                );
        });
    }

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required'
        ], [
            'mobile.required' => 'Mobile number is required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $mobile = $this->normalizeMobileToTenDigits($request->mobile);
        if (!$mobile) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid mobile number (minimum 10 digits)'
            ], 422);
        }
        
        // Auto-detect login type
        $loginType = $this->detectLoginType($mobile);

        if (!$loginType) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this mobile number. Please contact admin.'
            ], 404);
        }

        // Get entity for logging purposes
        $entity = null;
        switch ($loginType) {
            case 'employee':
                $userQ = User::query();
                $this->applyMobileMatch($userQ, 'mobile', $mobile);
                $entity = $userQ->first();
                break;
            case 'vendor':
                $vendorQ = Vendor::query();
                $this->applyMobileMatch($vendorQ, 'contact_no', $mobile);
                $entity = $vendorQ->first();
                break;
            case 'freelancer':
                $freelancerQ = JobRequest::query();
                $this->applyMobileMatch($freelancerQ, 'mobile', $mobile);
                $entity = $freelancerQ->first();
                if (!$entity) {
                    // Fallback: check contact_no for older records
                    $freelancerQ2 = JobRequest::query();
                    $this->applyMobileMatch($freelancerQ2, 'contact_no', $mobile);
                    $entity = $freelancerQ2->first();
                }
                break;
            case 'doctor_reg':
                $doctorQ = DoctorRequest::query();
                $this->applyMobileMatch($doctorQ, 'mobile', $mobile);
                $entity = $doctorQ->first();
                if (!$entity) {
                    $doctorQ2 = DoctorRequest::query();
                    $this->applyMobileMatch($doctorQ2, 'contact_no', $mobile);
                    $entity = $doctorQ2->first();
                }
                break;
            case 'customer':
                $leadQ = Lead::query();
                $this->applyMobileMatch($leadQ, 'contact_no', $mobile);
                $entity = $leadQ->first();
                if (!$entity) {
                    $operationLeadQ = OperationLead::query();
                    $this->applyMobileMatch($operationLeadQ, 'contact_no', $mobile);
                    $entity = $operationLeadQ->first();
                }
                break;
            case 'b2b_reference':
                $refQ = B2BReferenceUser::query();
                $this->applyMobileMatch($refQ, 'mobile', $mobile);
                $entity = $refQ->first();
                break;
            case 'doctor_referral':
                $drRefQ = DoctorReferralUser::query()->where('is_active', true);
                $this->applyMobileMatch($drRefQ, 'mobile', $mobile);
                $entity = $drRefQ->first();
                break;
            case 'b2b':
                $b2bQ = B2BUser::query();
                $this->applyMobileMatch($b2bQ, 'mobile', $mobile);
                $entity = $b2bQ->first();
                break;
        }

        $otp = $this->usesDemoFixedOtp($mobile)
            ? self::DEMO_OTP
            : str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        if (! $this->usesDemoFixedOtp($mobile)) {
            $sendResult = app(WabaLoginOtpService::class)->send($mobile, $otp);
            if (! $sendResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $sendResult['message'],
                ], 422);
            }
        }

        // Store OTP in cache with 10 minutes expiration (include login_type in key)
        $cacheKey = 'otp_'.$loginType.'_'.$mobile;
        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        // Also store login_type in cache for later use
        Cache::put('login_type_'.$mobile, $loginType, now()->addMinutes(10));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your WhatsApp number',
            'login_type' => $loginType,
        ]);
    }

    private function detectLoginType($mobile)
    {
        $mobile = $this->normalizeMobileToTenDigits($mobile);
        if (!$mobile) {
            return null;
        }

        // Auto-detect login type by checking in order of priority:
        // 1. B2B
        // 2. Freelancer (fix overlap: freelancer numbers should open freelancer portal)
        // 3. Customer
        // 4. Employee (only if has valid roles, not '0' or empty)
        // 5. Vendor / Doctor

        // B2B reference users (referrers) before companies so the same login flow can distinguish.
        $refQ = B2BReferenceUser::query()->where('is_active', true);
        $this->applyMobileMatch($refQ, 'mobile', $mobile);
        if ($refQ->first()) {
            return 'b2b_reference';
        }

        $docRefQ = DoctorReferralUser::query()->where('is_active', true);
        $this->applyMobileMatch($docRefQ, 'mobile', $mobile);
        if ($docRefQ->first()) {
            return 'doctor_referral';
        }

        // Check B2B company user.
        $b2bQ = B2BUser::query()->where('is_active', true);
        $this->applyMobileMatch($b2bQ, 'mobile', $mobile);
        $b2bUser = $b2bQ->first();
        if ($b2bUser) {
            return 'b2b';
        }

        // Check Freelancer before customer to avoid routing freelancer numbers to customer login.
        $freelancerQ = JobRequest::query();
        $this->applyMobileMatch($freelancerQ, 'mobile', $mobile);
        $freelancer = $freelancerQ->first();
        if (!$freelancer) {
            // Fallback: check contact_no for older records where mobile might not be set
            $freelancerQ2 = JobRequest::query();
            $this->applyMobileMatch($freelancerQ2, 'contact_no', $mobile);
            $freelancer = $freelancerQ2->first();
        }
        if ($freelancer) {
            return 'freelancer';
        }

        // Check Customer (in both Lead and OperationLead)
        $leadQ = Lead::query();
        $this->applyMobileMatch($leadQ, 'contact_no', $mobile);
        $lead = $leadQ->first();
        $operationLeadQ = OperationLead::query();
        $this->applyMobileMatch($operationLeadQ, 'contact_no', $mobile);
        $operationLead = $operationLeadQ->first();
        if ($lead || $operationLead) {
            return 'customer';
        }

        if (ConsultationWebsiteBooking::query()->whereTenDigitContact($mobile)->exists()) {
            return 'customer';
        }

        // Check Employee (only if has valid roles, not '0' or empty)
        $userQ = User::query();
        $this->applyMobileMatch($userQ, 'mobile', $mobile);
        $user = $userQ->first();
        if ($user) {
            $roleId = $user->role_id ?? '';
            if (!empty($roleId) && $roleId !== '0') {
                $roles = array_filter(
                    array_map('trim', explode(',', $roleId)),
                    function($roleId) {
                        return !empty($roleId) && $roleId !== '0' && is_numeric($roleId);
                    }
                );
                if (!empty($roles)) {
            return 'employee';
                }
            }
        }
        
        // Check Vendor
        $vendorQ = Vendor::query();
        $this->applyMobileMatch($vendorQ, 'contact_no', $mobile);
        $vendor = $vendorQ->first();
        if ($vendor) {
            return 'vendor';
        }

        $doctorQ = DoctorRequest::query();
        $this->applyMobileMatch($doctorQ, 'mobile', $mobile);
        $doctor = $doctorQ->first();
        if (!$doctor) {
            $doctorQ2 = DoctorRequest::query();
            $this->applyMobileMatch($doctorQ2, 'contact_no', $mobile);
            $doctor = $doctorQ2->first();
        }
        if ($doctor) {
            return 'doctor_reg';
        }
        return null;
    }

    public function login(Request $request)
    {
        // Check if it's an AJAX request
        if ($request->ajax() || $request->expectsJson()) {
            return $this->handleAjaxLogin($request);
        }

        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'otp' => 'required|regex:/^[0-9]{6}$/',
            'login_type' => 'nullable|in:employee,freelancer,vendor,customer,doctor_reg,b2b,b2b_reference,doctor_referral'
        ], [
            'mobile.required' => 'Mobile number is required',
            'otp.required' => 'OTP is required',
            'otp.regex' => 'Please enter a valid 6-digit OTP',
            'login_type.in' => 'Invalid login type'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return $this->authenticateUser($request);
    }

    private function handleAjaxLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'otp' => 'required|regex:/^[0-9]{6}$/',
            'login_type' => 'nullable|in:employee,freelancer,vendor,customer,doctor_reg,b2b,b2b_reference,doctor_referral'
        ], [
            'mobile.required' => 'Mobile number is required',
            'otp.required' => 'OTP is required',
            'otp.regex' => 'Please enter a valid 6-digit OTP',
            'login_type.in' => 'Invalid login type'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->authenticateUser($request);
            
            if ($result instanceof \Illuminate\Http\RedirectResponse) {
                return response()->json([
                    'success' => true,
                    'redirect' => $result->getTargetUrl()
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Login error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ], 500);
        }
    }

    private function authenticateUser(Request $request)
    {
        $mobile = $this->normalizeMobileToTenDigits($request->mobile);
        if (!$mobile) {
            $isAjax = $request->ajax() || $request->expectsJson();
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a valid mobile number (minimum 10 digits).'
                ], 422);
            }
            return redirect()->back()->with('error', 'Please enter a valid mobile number (minimum 10 digits).')->withInput();
        }
        $otp = $request->otp;
        $loginType = $request->login_type ?? Cache::get('login_type_' . $mobile);
        
        // If login_type not provided and not in cache, auto-detect it
        if (!$loginType) {
            $loginType = $this->detectLoginType($mobile);
        }
        
        if (!$loginType) {
            $isAjax = $request->ajax() || $request->expectsJson();
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to detect login type. Please try sending OTP again.'
                ], 422);
            }
            return redirect()->back()->with('error', 'Unable to detect login type. Please try sending OTP again.')->withInput();
        }
        
        $isAjax = $request->ajax() || $request->expectsJson();

        // Verify OTP (include login_type in cache key)
        $cacheKey = 'otp_' . $loginType . '_' . $mobile;
        $storedOtp = Cache::get($cacheKey);

        // Alternate OTP for registered numbers only (limited use)
        if (($otp === '989898') && $loginType) {
            $storedOtp = '989898';
        }

        if ($this->usesDemoFixedOtp($mobile) && $otp === self::DEMO_OTP && $loginType) {
            $storedOtp = self::DEMO_OTP;
        }

        if (!$storedOtp || $storedOtp !== $otp) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP. Please try again.'
                ], 422);
            }
            
            return redirect()->back()->with('error', 'Invalid OTP. Please try again.')->withInput();
        }

        // Authenticate based on login type
        switch ($loginType) {
            case 'employee':
                return $this->authenticateEmployee($mobile, $isAjax);
            
            case 'freelancer':
                return $this->authenticateFreelancer($mobile, $isAjax);

            case 'doctor_reg':
                return $this->authenticateDoctorReg($mobile, $isAjax);
            
            case 'vendor':
                return $this->authenticateVendor($mobile, $isAjax);
            
            case 'customer':
                return $this->authenticateCustomer($mobile, $isAjax);
            case 'b2b':
                return $this->authenticateB2B($mobile, $isAjax);
            case 'b2b_reference':
                return $this->authenticateB2BReference($mobile, $isAjax);

            case 'doctor_referral':
                return $this->authenticateDoctorReferral($mobile, $isAjax);

            default:
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid login type.'
                    ], 422);
                }
                return redirect()->back()->with('error', 'Invalid login type.')->withInput();
        }
    }

    private function authenticateEmployee($mobile, $isAjax)
    {
        $userQ = User::query();
        $this->applyMobileMatch($userQ, 'mobile', $mobile);
        $user = $userQ->first();

        if (!$user) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'No employee account found with this mobile number.'
                ], 404);
            }
            return redirect()->back()->with('error', 'No employee account found with this mobile number.')->withInput();
        }

        // Log the user in
        Auth::login($user);

        // Clear OTP from cache
        $cacheKey = 'otp_employee_' . $mobile;
        Cache::forget($cacheKey);
        Cache::forget('login_type_' . $mobile);

        // Handle role_id - filter out empty values and '0'
        $roleIdString = $user->role_id ?? '';
        if (empty($roleIdString) || $roleIdString === '0') {
                Auth::logout();
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'No roles assigned to this account.'
                ], 403);
            }
                return redirect()->back()->with('error', 'No roles assigned to this account.');
            }

        $roles = array_filter(
            array_map('trim', explode(',', $roleIdString)),
            function($roleId) {
                return !empty($roleId) && $roleId !== '0' && is_numeric($roleId);
            }
        );

        if (empty($roles)) {
            Auth::logout();
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid roles assigned to this account.'
                ], 403);
            }
            return redirect()->back()->with('error', 'No valid roles assigned to this account.');
        }

        // Convert to integers for database query
        $roleIds = array_map('intval', $roles);

        if (count($roleIds) > 1) {
            $roleNames = Role::whereIn('id', $roleIds)->pluck('name')->toArray();
            
            // Filter out any null values
            $roleNames = array_filter($roleNames);
            
            if (empty($roleNames)) {
                Auth::logout();
                Log::error('No valid roles found for user', [
                    'user_id' => $user->id,
                    'role_ids' => $roleIds
                ]);
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid role configuration.'
                    ], 500);
                }
                return redirect()->back()->with('error', 'Invalid role configuration.');
            }
            
            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'redirect' => route('login.role.select.show')
                ]);
            }
            
                return view('role-select', ['roles' => $roleNames]);
            }

        $role = Role::find($roleIds[0]);
            if (!$role) {
                Auth::logout();
            Log::error('Invalid role configuration for user', [
                'user_id' => $user->id,
                'role_id' => $roleIds[0],
                'role_id_string' => $roleIdString
            ]);
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid role configuration. Role ID: ' . $roleIds[0] . ' not found.'
                ], 500);
            }
                return redirect()->back()->with('error', 'Invalid role configuration.');
            }

            try {
                session()->put('logged_role', $role->id);
                session()->put('role_name', $role->name);
                session()->save();

            Log::info('Employee logged in successfully', [
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                'role_name' => $role->name,
                'mobile' => $mobile
            ]);

            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'redirect' => route($this->getRouteForRole($role->name))
                ]);
            }

                return $this->redirectBasedOnRole($role->name);
            } catch (\Exception $e) {
                Log::error('Failed to set session data', [
                    'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
                ]);
                Auth::logout();
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login failed. Please try again.'
                ], 500);
            }
            
                return redirect()->back()->with('error', 'Login failed. Please try again.');
            }
        }

    private function authenticateFreelancer($mobile, $isAjax)
    {
        // Check mobile first, then contact_no as fallback for older records
        $freelancerQ = JobRequest::query();
        $this->applyMobileMatch($freelancerQ, 'mobile', $mobile);
        $freelancer = $freelancerQ->first();
        if (!$freelancer) {
            // Fallback: check contact_no for older records where mobile might not be set
            $freelancerQ2 = JobRequest::query();
            $this->applyMobileMatch($freelancerQ2, 'contact_no', $mobile);
            $freelancer = $freelancerQ2->first();
        }

        if (!$freelancer) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'No freelancer account found with this mobile number.'
                ], 404);
            }
            return redirect()->back()->with('error', 'No freelancer account found with this mobile number.')->withInput();
        }

        // Set freelancer session
        session()->put('freelancer_id', $freelancer->id);
        session()->put('freelancer_name', $freelancer->name);
        session()->put('login_type', 'freelancer');
        session()->save();

        // Clear OTP from cache
        $cacheKey = 'otp_freelancer_' . $mobile;
        Cache::forget($cacheKey);
        Cache::forget('login_type_' . $mobile);

        Log::info('Freelancer logged in successfully', [
            'freelancer_id' => $freelancer->id,
            'name' => $freelancer->name,
            'mobile' => $mobile
        ]);

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'redirect' => route('freelancer.dashboard')
            ]);
        }

        return redirect()->route('freelancer.dashboard');
    }

    private function authenticateDoctorReg($mobile, $isAjax)
    {
        $doctorQ = DoctorRequest::query();
        $this->applyMobileMatch($doctorQ, 'mobile', $mobile);
        $doctor = $doctorQ->first();
        if (!$doctor) {
            $doctorQ2 = DoctorRequest::query();
            $this->applyMobileMatch($doctorQ2, 'contact_no', $mobile);
            $doctor = $doctorQ2->first();
        }

        if (!$doctor) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'No doctor registration found with this mobile number.',
                ], 404);
            }
            return redirect()->back()->with('error', 'No doctor registration found with this mobile number.')->withInput();
        }

        if ($doctor->approval_status === 'pending') {
            $msg = 'Your doctor registration is pending admin approval. You will be able to login after approval.';
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg)->withInput();
        }

        if ($doctor->approval_status === 'rejected') {
            $msg = 'Your doctor registration was rejected.';
            if ($doctor->admin_remark) {
                $msg .= ' ' . $doctor->admin_remark;
            }
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg)->withInput();
        }

        session()->put('doctor_reg_id', $doctor->id);
        session()->put('doctor_reg_name', $doctor->name);
        session()->put('login_type', 'doctor_reg');
        session()->save();

        $cacheKey = 'otp_doctor_reg_' . $mobile;
        Cache::forget($cacheKey);
        Cache::forget('login_type_' . $mobile);

        Log::info('Doctor portal logged in', ['doctor_request_id' => $doctor->id, 'mobile' => $mobile]);

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'redirect' => route('doctor_portal.dashboard'),
            ]);
        }

        return redirect()->route('doctor_portal.dashboard');
    }

    private function authenticateVendor($mobile, $isAjax)
    {
        $vendorQ = Vendor::query();
        $this->applyMobileMatch($vendorQ, 'contact_no', $mobile);
        $vendor = $vendorQ->first();

        if (!$vendor) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'No vendor account found with this mobile number.'
                ], 404);
            }
            return redirect()->back()->with('error', 'No vendor account found with this mobile number.')->withInput();
        }

        // Set vendor session
        session()->put('vendor_id', $vendor->id);
        session()->put('vendor_name', $vendor->name);
        session()->put('login_type', 'vendor');
        session()->save();

        // Clear OTP from cache
        $cacheKey = 'otp_vendor_' . $mobile;
        Cache::forget($cacheKey);
        Cache::forget('login_type_' . $mobile);

        Log::info('Vendor logged in successfully', [
            'vendor_id' => $vendor->id,
            'name' => $vendor->name,
            'mobile' => $mobile
        ]);

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'redirect' => route('vendor.dashboard')
            ]);
        }

        return redirect()->route('vendor.dashboard');
    }

    private function authenticateCustomer($mobile, $isAjax)
    {
        // Check in both Lead and OperationLead tables
        $leadQ = Lead::query();
        $this->applyMobileMatch($leadQ, 'contact_no', $mobile);
        $lead = $leadQ->first();
        $operationLeadQ = OperationLead::query();
        $this->applyMobileMatch($operationLeadQ, 'contact_no', $mobile);
        $operationLead = $operationLeadQ->first();

        if ($lead || $operationLead) {
            $customer = $lead ?? $operationLead;
            $customerType = $lead ? 'lead' : 'operation_lead';

            session()->put('customer_id', $customer->id);
            session()->put('customer_name', $customer->customer_name ?? 'Customer');
            session()->put('customer_type', $customerType);
            session()->put('contact_no', $customer->contact_no ?? $mobile);
        } else {
            $booking = ConsultationWebsiteBooking::query()
                ->whereTenDigitContact($mobile)
                ->orderByDesc('appointment_date')
                ->orderByDesc('id')
                ->first();

            if (!$booking) {
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No customer account found with this mobile number.'
                    ], 404);
                }

                return redirect()->back()->with('error', 'No customer account found with this mobile number.')->withInput();
            }

            session()->put('customer_id', -1);
            session()->put('customer_name', $booking->customer_name ?? 'Customer');
            session()->put('customer_type', 'consultation_booking');
            session()->put('contact_no', ConsultationWebsiteBooking::normalizeContactToTenDigits($booking->contact_no) ?? $mobile);
        }

        session()->put('login_type', 'customer');
        session()->save();

        Cache::forget('otp_customer_' . $mobile);
        Cache::forget('login_type_' . $mobile);

        Log::info('Customer logged in successfully', [
            'customer_id' => Session::get('customer_id'),
            'customer_type' => Session::get('customer_type'),
            'mobile' => $mobile
        ]);

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'redirect' => route('customer.dashboard')
            ]);
        }

        return redirect()->route('customer.dashboard');
    }

    private function authenticateB2B($mobile, $isAjax)
    {
        $b2bQ = B2BUser::query()->where('is_active', true);
        $this->applyMobileMatch($b2bQ, 'mobile', $mobile);
        $b2bUser = $b2bQ->first();
        if (!$b2bUser) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active B2B account found with this mobile number.'
                ], 404);
            }
            return redirect()->back()->with('error', 'No active B2B account found with this mobile number.')->withInput();
        }

        session()->put('b2b_user_id', $b2bUser->id);
        session()->put('b2b_user_name', $b2bUser->name);
        session()->put('login_type', 'b2b');
        session()->save();

        $cacheKey = 'otp_b2b_' . $mobile;
        Cache::forget($cacheKey);
        Cache::forget('login_type_' . $mobile);

        $dashboardRoute = match ($b2bUser->account_type) {
            B2BUser::TYPE_CORPORATE => 'b2b.corporate.dashboard',
            B2BUser::TYPE_INDIVIDUAL => 'b2b.individual.dashboard',
            default => 'b2b.dashboard',
        };

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'redirect' => route($dashboardRoute),
            ]);
        }

        return redirect()->route($dashboardRoute);
    }

    private function authenticateB2BReference($mobile, $isAjax)
    {
        $refQ = B2BReferenceUser::query()->where('is_active', true);
        $this->applyMobileMatch($refQ, 'mobile', $mobile);
        $refUser = $refQ->first();
        if (! $refUser) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active B2B reference account found with this mobile number.',
                ], 404);
            }

            return redirect()->back()->with('error', 'No active B2B reference account found with this mobile number.')->withInput();
        }

        session()->put('b2b_reference_user_id', $refUser->id);
        session()->put('b2b_reference_user_name', $refUser->name);
        session()->put('login_type', 'b2b_reference');
        session()->save();

        Cache::forget('otp_b2b_reference_' . $mobile);
        Cache::forget('login_type_' . $mobile);

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'redirect' => route('b2b_reference.dashboard'),
            ]);
        }

        return redirect()->route('b2b_reference.dashboard');
    }

    private function authenticateDoctorReferral($mobile, $isAjax)
    {
        $refQ = DoctorReferralUser::query()->where('is_active', true);
        $this->applyMobileMatch($refQ, 'mobile', $mobile);
        $refUser = $refQ->first();
        if (! $refUser) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active doctor referral account found with this mobile number.',
                ], 404);
            }

            return redirect()->back()->with('error', 'No active doctor referral account found with this mobile number.')->withInput();
        }

        session()->put('doctor_referral_user_id', $refUser->id);
        session()->put('doctor_referral_user_name', $refUser->name);
        session()->put('login_type', 'doctor_referral');
        session()->save();

        Cache::forget('otp_doctor_referral_' . $mobile);
        Cache::forget('login_type_' . $mobile);

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'redirect' => route('doctor_referral.dashboard'),
            ]);
        }

        return redirect()->route('doctor_referral.dashboard');
    }

    private function getRouteForRole($role)
    {
        $routes = [
            'Admin' => 'admin.dashboard',
            'Sales' => 'sales.dashboard',
            'Sales Manager' => 'manager.dashboard',
            'Operation Manager' => 'operation-manager.dashboard',
            'Operation' => 'operation.dashboard',
            'B2B' => 'b2b.dashboard',
            'Sub Admin' => 'subadmin.dashboard'
        ];

        return $routes[$role] ?? 'home';
    }

    public function showRoleSelect(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('home')->with('error', 'Please login first');
        }

        $user = Auth::user();
        $roleIdString = $user->role_id ?? '';
        
        $roles = array_filter(
            array_map('trim', explode(',', $roleIdString)),
            function($roleId) {
                return !empty($roleId) && $roleId !== '0' && is_numeric($roleId);
            }
        );
        
        $roleIds = array_map('intval', $roles);
        $roleNames = \App\Models\Role::whereIn('id', $roleIds)->pluck('name')->toArray();
        $roleNames = array_filter($roleNames);

        return view('role-select', ['roles' => $roleNames]);
    }

    public function selectRole(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('home')->with('error', 'Please login first');
        }

        $roleName = $request->input('role');
        $user = Auth::user();

        if (!$roleName) {
            Log::warning('Role selection attempted without role name', ['user_id' => $user->id]);
            return redirect()->back()->with('error', 'Please select a role');
        }

        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            Log::error('Invalid role selected', [
                'user_id' => $user->id,
                'role_name' => $roleName
            ]);
            return redirect()->back()->with('error', 'Invalid role selected');
        }

        // Handle role_id - filter out empty values and '0'
        $roleIdString = $user->role_id ?? '';
        if (empty($roleIdString) || $roleIdString === '0') {
            Log::warning('No roles assigned to user', [
                'user_id' => $user->id,
                'attempted_role' => $roleName
            ]);
            return redirect()->back()->with('error', 'No roles assigned to this account.');
        }

        $userRoles = array_filter(
            array_map('trim', explode(',', $roleIdString)),
            function($roleId) {
                return !empty($roleId) && $roleId !== '0' && is_numeric($roleId);
            }
        );

        $userRoleIds = array_map('intval', $userRoles);

        if (!in_array($role->id, $userRoleIds)) {
            Log::warning('Unauthorized role selection attempt', [
                'user_id' => $user->id,
                'attempted_role' => $roleName,
                'attempted_role_id' => $role->id,
                'user_roles' => $userRoleIds
            ]);
            return redirect()->back()->with('error', 'You are not authorized to select this role');
        }

        try {
            session()->put('logged_role', $role->id);
            session()->put('role_name', $role->name);
            session()->save();

            Log::info('Role selected successfully', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            return $this->redirectBasedOnRole($roleName);
        } catch (\Exception $e) {
            Log::error('Failed to set role session data', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Failed to select role. Please try again.');
        }
    }

    private function redirectBasedOnRole($role)
    {
        $routes = [
            'Admin' => 'admin.dashboard',
            'Sales' => 'sales.dashboard',
            'Sales Manager' => 'manager.dashboard',
            'Operation Manager' => 'operation-manager.dashboard',
            'Operation' => 'operation.dashboard',
            'B2B' => 'b2b.dashboard',
            'Sub Admin' => 'subadmin.dashboard'
        ];

        if (!isset($routes[$role])) {
            Log::error('No route defined for role: ' . $role);
            return redirect()->route('home')->with('error', 'Invalid role configuration');
    }

        return redirect()->route($routes[$role]);
    }

    public function logout()
    {
        $userId = Auth::id();
        $roleName = session('role_name');
        $loginType = session('login_type');

        Log::info('User logged out', [
            'user_id' => $userId,
            'role_name' => $roleName,
            'login_type' => $loginType
        ]);

        // Clear all session data for all login types
        Session::forget([
            'logged_role',
            'role_name',
            'login_type',
            'freelancer_id',
            'freelancer_name',
            'doctor_reg_id',
            'doctor_reg_name',
            'vendor_id',
            'vendor_name',
            'customer_id',
            'customer_name',
            'customer_type',
            'b2b_user_id',
            'b2b_user_name',
            'b2b_reference_user_id',
            'b2b_reference_user_name',
            'doctor_referral_user_id',
            'doctor_referral_user_name',
            'insurer_user_id',
            'insurer_user_name',
            'broker_user_id',
            'broker_user_name',
            'corporate_user_id',
            'corporate_user_name',
            'corporate_username',
            'corporate_created_by',
            'corporate_employee_id',
            'corporate_employee_name',
            'corporate_employee_code',
        ]);

        Session::flush();
        Auth::logout();
        return redirect()->route('home');
    }

    public function update_profile_image(Request $request, $member_id = null)
    {
        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/profile_images', $filename);

            $user = $member_id ? User::find($member_id) : Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found']);
            }

            $user->profile_image = 'profile_images/' . $filename;
            $user->save();

            return response()->json(['success' => true, 'message' => 'Profile image updated successfully']);
        }

        return response()->json(['success' => false, 'message' => 'No image file found']);
    }
}
