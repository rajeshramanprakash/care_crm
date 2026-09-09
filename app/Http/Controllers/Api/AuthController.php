<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\B2BReferenceUser;
use App\Models\DoctorReferralUser;
use App\Models\B2BUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;    
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Models\ConsultationWebsiteBooking;
use App\Services\ExpoNotificationService;
use App\Services\WabaLoginOtpService;

class AuthController extends Controller
{
    /** Fixed OTP login for demo / QA numbers (no WhatsApp send). */
    private const DEMO_OTP = '999999';

    private const DEMO_OTP_MOBILES = [
        '775496128',
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
        // Supports records saved as 10-digit, 91+10, +91+10, or formatted text.
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

    private function resolveMobileFromRequest(Request $request): ?string
    {
        $raw = $request->input('mobile');
        if ($raw === null || $raw === '') {
            $raw = $request->query('mobile');
        }

        return $this->normalizeMobileToTenDigits($raw);
    }

    public function login(Request $request)
    {
        try {
            // Check if it's mobile + OTP login or email + password login
            if ($request->has('mobile') && $request->has('otp')) {
                return $this->loginWithOtp($request);
            }

            // Legacy email + password login
        Log::info("Api");
        Log::info($request);
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
        } catch (\Exception $e) {
            Log::error('Login error in API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login. Please try again.'
            ], 500);
        }
    }

    public function sendOtp(Request $request)
    {
        $mobile = $this->resolveMobileFromRequest($request);
        if (!$mobile) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid mobile number'
            ], 422);
        }
        
        // Auto-detect login type - check all types
        $loginType = $this->detectLoginType($mobile);
        
        if (!$loginType) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this mobile number. Please contact admin.'
            ], 404);
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

        // Store OTP in cache with 10 minutes expiration
        $cacheKey = 'otp_'.$loginType.'_'.$mobile;
        Cache::put($cacheKey, $otp, now()->addMinutes(10));
        Cache::put('login_type_'.$mobile, $loginType, now()->addMinutes(10));

        // Also store OTP with a generic key (without login_type) as fallback
        $genericCacheKey = 'otp_'.$mobile;
        Cache::put($genericCacheKey, $otp, now()->addMinutes(10));

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
        // 2. Freelancer (fix overlap: freelancer numbers should open freelancer app)
        // 3. Customer
        // 4. Employee
        // 5. Vendor / Doctor
        
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

        $b2bQ = B2BUser::query()->where('is_active', true);
        $this->applyMobileMatch($b2bQ, 'mobile', $mobile);
        $b2b = $b2bQ->first();
        if ($b2b) {
            return 'b2b';
        }
        
        // Check Freelancer before customer to avoid routing freelancer numbers to customer login.
        $freelancerQ = \App\Models\JobRequest::query();
        $this->applyMobileMatch($freelancerQ, 'mobile', $mobile);
        $freelancer = $freelancerQ->first();
        if (!$freelancer) {
            // Fallback: check contact_no for older records
            $freelancerQ2 = \App\Models\JobRequest::query();
            $this->applyMobileMatch($freelancerQ2, 'contact_no', $mobile);
            $freelancer = $freelancerQ2->first();
        }
        if ($freelancer) {
            return 'freelancer';
        }

        // Check Customer (in both Lead and OperationLead)
        $leadQ = \App\Models\Lead::query();
        $this->applyMobileMatch($leadQ, 'contact_no', $mobile);
        $lead = $leadQ->first();
        $operationLeadQ = \App\Models\OperationLead::query();
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
        $vendorQ = \App\Models\Vendor::query();
        $this->applyMobileMatch($vendorQ, 'contact_no', $mobile);
        $vendor = $vendorQ->first();
        if ($vendor) {
            return 'vendor';
        }

        $doctorQ = \App\Models\DoctorRequest::query();
        $this->applyMobileMatch($doctorQ, 'mobile', $mobile);
        $doctor = $doctorQ->first();
        if (!$doctor) {
            $doctorQ2 = \App\Models\DoctorRequest::query();
            $this->applyMobileMatch($doctorQ2, 'contact_no', $mobile);
            $doctor = $doctorQ2->first();
        }
        if ($doctor) {
            return 'doctor_reg';
        }
        return null;
    }

    private function loginWithOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'otp' => 'required|regex:/^[0-9]{6}$/',
            ], [
                'otp.required' => 'OTP is required',
                'otp.regex' => 'Please enter a valid 6-digit OTP',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $mobile = $this->resolveMobileFromRequest($request);
            if (!$mobile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a valid mobile number'
                ], 422);
            }
            $otp = (string) $request->otp; // Ensure OTP is a string

            // Alternate OTP for registered numbers only (limited use)
            $storedOtp = null;
            $verifiedLoginType = null;
            if ($otp === '989898') {
                $verifiedLoginType = $this->detectLoginType($mobile);
                if ($verifiedLoginType) {
                    $storedOtp = '989898';
                    Cache::put('login_type_' . $mobile, $verifiedLoginType, now()->addMinutes(10));
                    Cache::put('otp_' . $verifiedLoginType . '_' . $mobile, '989898', now()->addMinutes(10));
                    Cache::put('otp_' . $mobile, '989898', now()->addMinutes(10));
                }
            } elseif ($otp === self::DEMO_OTP && $this->usesDemoFixedOtp($mobile)) {
                $verifiedLoginType = $this->detectLoginType($mobile);
                if ($verifiedLoginType) {
                    $storedOtp = self::DEMO_OTP;
                    Cache::put('login_type_' . $mobile, $verifiedLoginType, now()->addMinutes(10));
                    Cache::put('otp_' . $verifiedLoginType . '_' . $mobile, self::DEMO_OTP, now()->addMinutes(10));
                    Cache::put('otp_' . $mobile, self::DEMO_OTP, now()->addMinutes(10));
                }
            }

            // Get login type from cache first
            $loginType = Cache::get('login_type_' . $mobile);
            
            // If not in cache, try to detect login type
            if (!$loginType) {
                $loginType = $this->detectLoginType($mobile);
            }

            Log::info('OTP verification attempt', [
                'mobile' => $mobile,
                'attempted_otp' => $otp,
                'otp_type' => gettype($otp),
                'login_type_from_cache' => $loginType,
            ]);

            // Verify OTP - try with cached login_type first, then try all possible types
            // (If master OTP already validated above, skip cache verification)
            if ($verifiedLoginType && $storedOtp === $otp) {
                // continue with verified login type
            } elseif ($loginType) {
                // Try with the detected/cached login type first
                $cacheKey = 'otp_' . $loginType . '_' . $mobile;
                $storedOtp = Cache::get($cacheKey);
                $storedOtp = $storedOtp ? (string) $storedOtp : null; // Ensure stored OTP is a string
                Log::info('Checking OTP with login type', [
                    'login_type' => $loginType,
                    'cache_key' => $cacheKey,
                    'stored_otp' => $storedOtp,
                    'stored_otp_type' => gettype($storedOtp),
                    'matches' => ($storedOtp && $storedOtp === $otp)
                ]);
                if ($storedOtp && $storedOtp === $otp) {
                    $verifiedLoginType = $loginType;
                }
            }
            
            // If not found, try all possible login types
            if (!$verifiedLoginType) {
                $possibleTypes = ['employee', 'customer', 'vendor', 'doctor_reg', 'freelancer', 'b2b', 'b2b_reference', 'doctor_referral'];
                foreach ($possibleTypes as $type) {
                    $cacheKey = 'otp_' . $type . '_' . $mobile;
                    $storedOtp = Cache::get($cacheKey);
                    $storedOtp = $storedOtp ? (string) $storedOtp : null; // Ensure stored OTP is a string
                    Log::info('Checking OTP with alternative login type', [
                        'login_type' => $type,
                        'cache_key' => $cacheKey,
                        'stored_otp' => $storedOtp,
                        'stored_otp_type' => gettype($storedOtp),
                        'matches' => ($storedOtp && $storedOtp === $otp)
                    ]);
                    if ($storedOtp && $storedOtp === $otp) {
                        $verifiedLoginType = $type;
                        // Update the login_type cache
                        Cache::put('login_type_' . $mobile, $type, now()->addMinutes(10));
                        break;
                    }
                }
            }
            
            // If still not found, try generic cache key (fallback)
            if (!$verifiedLoginType) {
                $genericCacheKey = 'otp_' . $mobile;
                $storedOtp = Cache::get($genericCacheKey);
                $storedOtp = $storedOtp ? (string) $storedOtp : null; // Ensure stored OTP is a string
                Log::info('Checking OTP with generic cache key', [
                    'cache_key' => $genericCacheKey,
                    'stored_otp' => $storedOtp,
                    'stored_otp_type' => gettype($storedOtp),
                    'matches' => ($storedOtp && $storedOtp === $otp)
                ]);
                if ($storedOtp && $storedOtp === $otp) {
                    // OTP found in generic cache, detect login type again
                    $verifiedLoginType = $this->detectLoginType($mobile);
                    if ($verifiedLoginType) {
                        // Store with the detected login type for consistency
                        $cacheKey = 'otp_' . $verifiedLoginType . '_' . $mobile;
                        Cache::put($cacheKey, $otp, now()->addMinutes(10));
                        Cache::put('login_type_' . $mobile, $verifiedLoginType, now()->addMinutes(10));
                    }
                }
            }

            if (!$verifiedLoginType || !$storedOtp || $storedOtp !== $otp) {
                Log::warning('OTP verification failed', [
                    'mobile' => $mobile,
                    'attempted_otp' => $otp,
                    'attempted_otp_type' => gettype($otp),
                    'login_type_from_cache' => $loginType,
                    'verified_login_type' => $verifiedLoginType,
                    'stored_otp' => $storedOtp,
                    'stored_otp_type' => gettype($storedOtp),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP. Please try again.'
                ], 422);
            }

            // Use the verified login type
            $loginType = $verifiedLoginType;
            $cacheKey = 'otp_' . $loginType . '_' . $mobile;
            
            Log::info('OTP verification successful', [
                'mobile' => $mobile,
                'login_type' => $loginType,
            ]);

            // Handle different login types
            if ($loginType === 'employee') {
                return $this->handleEmployeeLogin($mobile, $cacheKey);
            } elseif ($loginType === 'customer') {
                return $this->handleCustomerLogin($mobile, $cacheKey);
            } elseif ($loginType === 'vendor') {
                return $this->handleVendorLogin($mobile, $cacheKey);
            } elseif ($loginType === 'freelancer') {
                return $this->handleFreelancerLogin($mobile, $cacheKey);
            } elseif ($loginType === 'doctor_reg') {
                return $this->handleDoctorRegLogin($mobile, $cacheKey);
            } elseif ($loginType === 'b2b') {
                return $this->handleB2BLogin($mobile, $cacheKey);
            } elseif ($loginType === 'b2b_reference') {
                return $this->handleB2BReferenceLogin($mobile, $cacheKey);
            } elseif ($loginType === 'doctor_referral') {
                return $this->handleDoctorReferralLogin($mobile, $cacheKey);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Please use the web portal to login as ' . $loginType
                ], 403);
            }
        } catch (\Exception $e) {
            Log::error('Error in loginWithOtp', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'mobile' => $request->mobile ?? 'N/A',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login. Please try again.'
            ], 500);
        }
    }

    private function handleEmployeeLogin($mobile, $cacheKey)
    {
        try {
            $userQ = User::query();
            $this->applyMobileMatch($userQ, 'mobile', $mobile);
            $user = $userQ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No employee account found with this mobile number.'
                ], 404);
            }

            // Create token
            $token = $user->createToken('mobile-app')->plainTextToken;

            // Clear OTP from cache
            Cache::forget($cacheKey);
            Cache::forget('login_type_' . $mobile);

            Log::info('Employee logged in via API', [
                'user_id' => $user->id,
                'mobile' => $mobile
            ]);

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => $user,
                'login_type' => 'employee'
            ]);
        } catch (\Exception $e) {
            Log::error('Employee login error', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login. Please try again or contact support.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function handleCustomerLogin($mobile, $cacheKey)
    {
        try {
            $leadQ = \App\Models\Lead::query();
            $this->applyMobileMatch($leadQ, 'contact_no', $mobile);
            $lead = $leadQ->first();
            $operationLeadQ = \App\Models\OperationLead::query();
            $this->applyMobileMatch($operationLeadQ, 'contact_no', $mobile);
            $operationLead = $operationLeadQ->first();
            $crmCustomer = $operationLead ?? $lead;

            $bookingOnly = null;
            if (!$crmCustomer) {
                $bookingOnly = ConsultationWebsiteBooking::query()
                    ->whereTenDigitContact($mobile)
                    ->orderByDesc('appointment_date')
                    ->orderByDesc('id')
                    ->first();
                if (!$bookingOnly) {
                return response()->json([
                    'success' => false,
                    'message' => 'No customer account found with this mobile number.'
                ], 404);
                }
            }

            $displayName = $crmCustomer?->customer_name ?? ($bookingOnly?->customer_name ?? 'Customer');

            // Check if user exists (including soft-deleted)
            $userQ = User::withTrashed();
            $this->applyMobileMatch($userQ, 'mobile', $mobile);
            $user = $userQ->first();
            
            if (!$user) {
                // Generate unique email
                $email = 'customer_' . $mobile . '@carelix.com';
                $counter = 1;
                
                // Check if email already exists and generate unique one
                while (User::withTrashed()->where('email', $email)->exists()) {
                    $email = 'customer_' . $mobile . '_' . $counter . '@carelix.com';
                    $counter++;
                }
                
                // Create a temporary user for API access
                try {
                    $user = User::create([
                        'mobile' => $mobile,
                        'f_name' => $displayName,
                        'l_name' => '',
                        'email' => $email,
                        'password' => bcrypt(uniqid()), // Random password
                        'role_id' => '0', // Special role for customers
                        'is_active' => 1,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // If still fails, try to find existing user by email or restore soft-deleted
                    Log::error('Error creating customer user', [
                        'mobile' => $mobile,
                        'error' => $e->getMessage(),
                        'customer_id' => $crmCustomer?->id,
                    ]);
                    
                    // Try to find by email pattern
                    $existingUser = User::withTrashed()
                        ->where('email', 'like', 'customer_' . $mobile . '%@carelix.com')
                        ->first();
                    
                    if ($existingUser) {
                        if ($existingUser->trashed()) {
                            $existingUser->restore();
                        }
                        $user = $existingUser;
                    } else {
                        throw $e;
                    }
                }
            } else {
                // User exists, restore if soft-deleted
                if ($user->trashed()) {
                    $user->restore();
                }
                
                // Update user info if needed
                $user->f_name = $displayName;
                $user->l_name = '';
                $user->is_active = 1;
                $user->save();
            }

            // Create token
            $token = $user->createToken('mobile-app')->plainTextToken;

            // Clear OTP from cache
            Cache::forget($cacheKey);
            Cache::forget('login_type_' . $mobile);

            if ($crmCustomer) {
            $finalCustomerType = $operationLead ? 'operation_lead' : 'lead';
                $finalCustomerId = $crmCustomer->id;
            
            Cache::put('customer_info_' . $user->id, [
                'customer_id' => $finalCustomerId,
                'customer_type' => $finalCustomerType,
                    'customer_name' => $crmCustomer->customer_name ?? 'Customer',
                    'contact_no' => $crmCustomer->contact_no ?? $mobile,
                ], now()->addDays(30));
            } else {
                $contactNorm = ConsultationWebsiteBooking::normalizeContactToTenDigits($bookingOnly->contact_no) ?? $mobile;
                Cache::put('customer_info_' . $user->id, [
                    'customer_id' => -1,
                    'customer_type' => 'consultation_booking',
                    'customer_name' => $bookingOnly->customer_name ?? 'Customer',
                    'contact_no' => $contactNorm,
            ], now()->addDays(30));

                $finalCustomerType = 'consultation_booking';
                $finalCustomerId = -1;
            }

            Log::info('Customer logged in via API', [
                'user_id' => $user->id,
                'customer_id' => $finalCustomerId,
                'customer_type' => $finalCustomerType,
                'mobile' => $mobile
            ]);

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => $user,
                'login_type' => 'customer',
                'customer' => [
                    'id' => $finalCustomerId,
                    'customer_type' => $finalCustomerType,
                    'customer_name' => $displayName,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Customer login error', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login. Please try again or contact support.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function handleVendorLogin($mobile, $cacheKey)
    {
        try {
            $vendorQ = \App\Models\Vendor::query();
            $this->applyMobileMatch($vendorQ, 'contact_no', $mobile);
            $vendor = $vendorQ->first();
            
            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'No vendor account found with this mobile number.'
                ], 404);
            }

            // Check if user exists (including soft-deleted)
            $userQ = User::withTrashed();
            $this->applyMobileMatch($userQ, 'mobile', $mobile);
            $user = $userQ->first();
            
            if (!$user) {
                // Generate unique email
                $email = 'vendor_' . $mobile . '@carelix.com';
                $counter = 1;
                
                // Check if email already exists and generate unique one
                while (User::withTrashed()->where('email', $email)->exists()) {
                    $email = 'vendor_' . $mobile . '_' . $counter . '@carelix.com';
                    $counter++;
                }
                
                // Create a temporary user for API access
                try {
                    $user = User::create([
                        'mobile' => $mobile,
                        'f_name' => $vendor->name ?? 'Vendor',
                        'l_name' => '',
                        'email' => $email,
                        'password' => bcrypt(uniqid()), // Random password
                        'role_id' => '0', // Special role for vendors
                        'is_active' => 1,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // If still fails, try to find existing user by email or restore soft-deleted
                    Log::error('Error creating vendor user', [
                        'mobile' => $mobile,
                        'error' => $e->getMessage(),
                        'vendor_id' => $vendor->id
                    ]);
                    
                    // Try to find by email pattern
                    $existingUser = User::withTrashed()
                        ->where('email', 'like', 'vendor_' . $mobile . '%@carelix.com')
                        ->first();
                    
                    if ($existingUser) {
                        if ($existingUser->trashed()) {
                            $existingUser->restore();
                        }
                        $user = $existingUser;
                    } else {
                        throw $e;
                    }
                }
            } else {
                // User exists, restore if soft-deleted
                if ($user->trashed()) {
                    $user->restore();
                }
                
                // Update user info if needed
                $user->f_name = $vendor->name ?? 'Vendor';
                $user->l_name = '';
                $user->is_active = 1;
                $user->save();
            }

            // Create token
            $token = $user->createToken('mobile-app')->plainTextToken;

            // Clear OTP from cache
            Cache::forget($cacheKey);
            Cache::forget('login_type_' . $mobile);

            // Store vendor info in cache for API access
            Cache::put('vendor_info_' . $user->id, [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name ?? 'Vendor',
                'contact_no' => $vendor->contact_no ?? $mobile,
            ], now()->addDays(30));

            Log::info('Vendor logged in via API', [
                'user_id' => $user->id,
                'vendor_id' => $vendor->id,
                'mobile' => $mobile
            ]);

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => $user,
                'login_type' => 'vendor',
                'vendor' => [
                    'id' => $vendor->id,
                    'name' => $vendor->name ?? 'Vendor',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Vendor login error', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login. Please try again or contact support.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function handleFreelancerLogin($mobile, $cacheKey)
    {
        try {
            // Check mobile first, then contact_no as fallback
            $freelancerQ = \App\Models\JobRequest::query();
            $this->applyMobileMatch($freelancerQ, 'mobile', $mobile);
            $freelancer = $freelancerQ->first();
            if (!$freelancer) {
                $freelancerQ2 = \App\Models\JobRequest::query();
                $this->applyMobileMatch($freelancerQ2, 'contact_no', $mobile);
                $freelancer = $freelancerQ2->first();
            }
            
            if (!$freelancer) {
                return response()->json([
                    'success' => false,
                    'message' => 'No freelancer account found with this mobile number.'
                ], 404);
            }

            // Check if user exists (including soft-deleted)
            $userQ = User::withTrashed();
            $this->applyMobileMatch($userQ, 'mobile', $mobile);
            $user = $userQ->first();
            
            if (!$user) {
                // Generate unique email
                $email = 'freelancer_' . $mobile . '@carelix.com';
                $counter = 1;
                
                // Check if email already exists and generate unique one
                while (User::withTrashed()->where('email', $email)->exists()) {
                    $email = 'freelancer_' . $mobile . '_' . $counter . '@carelix.com';
                    $counter++;
                }
                
                // Create a temporary user for API access
                try {
                    $user = User::create([
                        'mobile' => $mobile,
                        'f_name' => $freelancer->name ?? 'Freelancer',
                        'l_name' => '',
                        'email' => $email,
                        'password' => bcrypt(uniqid()), // Random password
                        'role_id' => '0', // Special role for freelancers
                        'is_active' => 1,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // If still fails, try to find existing user by email or restore soft-deleted
                    Log::error('Error creating freelancer user', [
                        'mobile' => $mobile,
                        'error' => $e->getMessage(),
                        'freelancer_id' => $freelancer->id
                    ]);
                    
                    // Try to find by email pattern
                    $existingUser = User::withTrashed()
                        ->where('email', 'like', 'freelancer_' . $mobile . '%@carelix.com')
                        ->first();
                    
                    if ($existingUser) {
                        if ($existingUser->trashed()) {
                            $existingUser->restore();
                        }
                        $user = $existingUser;
                    } else {
                        throw $e;
                    }
                }
            } else {
                // User exists, restore if soft-deleted
                if ($user->trashed()) {
                    $user->restore();
                }
                
                // Update user info if needed
                $user->f_name = $freelancer->name ?? 'Freelancer';
                $user->l_name = '';
                $user->is_active = 1;
                $user->save();
            }

            // Create token
            $token = $user->createToken('mobile-app')->plainTextToken;

            // Clear OTP from cache
            Cache::forget($cacheKey);
            Cache::forget('login_type_' . $mobile);

            // Store freelancer info in cache for API access
            Cache::put('freelancer_info_' . $user->id, [
                'freelancer_id' => $freelancer->id,
                'freelancer_name' => $freelancer->name ?? 'Freelancer',
                'contact_no' => $freelancer->contact_no ?? $freelancer->mobile ?? $mobile,
            ], now()->addDays(30));

            Log::info('Freelancer logged in via API', [
                'user_id' => $user->id,
                'freelancer_id' => $freelancer->id,
                'mobile' => $mobile
            ]);

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => $user,
                'login_type' => 'freelancer',
                'freelancer' => [
                    'id' => $freelancer->id,
                    'name' => $freelancer->name ?? 'Freelancer',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Freelancer login error', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login. Please try again or contact support.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function handleDoctorRegLogin($mobile, $cacheKey)
    {
        try {
            $doctorQ = \App\Models\DoctorRequest::query();
            $this->applyMobileMatch($doctorQ, 'mobile', $mobile);
            $doctor = $doctorQ->first();
            if (!$doctor) {
                $doctorQ2 = \App\Models\DoctorRequest::query();
                $this->applyMobileMatch($doctorQ2, 'contact_no', $mobile);
                $doctor = $doctorQ2->first();
            }
            if (!$doctor) {
        return response()->json([
                    'success' => false,
                    'message' => 'No doctor registration found with this mobile number.',
                ], 404);
            }
            if ($doctor->approval_status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your doctor registration is pending admin approval.',
                ], 403);
            }
            if ($doctor->approval_status === 'rejected') {
                $msg = 'Your doctor registration was rejected.';
                if ($doctor->admin_remark) {
                    $msg .= ' ' . $doctor->admin_remark;
                }
                return response()->json(['success' => false, 'message' => $msg], 403);
            }

            $userQ = User::withTrashed();
            $this->applyMobileMatch($userQ, 'mobile', $mobile);
            $user = $userQ->first();
            if (!$user) {
                $email = 'doctor_' . $mobile . '@carelix.com';
                $counter = 1;
                while (User::withTrashed()->where('email', $email)->exists()) {
                    $email = 'doctor_' . $mobile . '_' . $counter . '@carelix.com';
                    $counter++;
                }
                try {
                    $user = User::create([
                        'mobile' => $mobile,
                        'f_name' => $doctor->name ?? 'Doctor',
                        'l_name' => '',
                        'email' => $email,
                        'password' => bcrypt(uniqid()),
                        'role_id' => '0',
                        'is_active' => 1,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    $existingUser = User::withTrashed()
                        ->where('email', 'like', 'doctor_' . $mobile . '%@carelix.com')
                        ->first();
                    if ($existingUser) {
                        if ($existingUser->trashed()) {
                            $existingUser->restore();
                        }
                        $user = $existingUser;
                    } else {
                        throw $e;
                    }
                }
            } else {
                if ($user->trashed()) {
                    $user->restore();
                }
                $user->f_name = $doctor->name ?? 'Doctor';
                $user->l_name = '';
                $user->is_active = 1;
                $user->save();
            }

            $token = $user->createToken('mobile-app')->plainTextToken;
            Cache::forget($cacheKey);
            Cache::forget('login_type_' . $mobile);

            Cache::put('doctor_reg_info_' . $user->id, [
                'doctor_request_id' => $doctor->id,
                'doctor_name' => $doctor->name ?? 'Doctor',
                'contact_no' => $doctor->contact_no ?? $doctor->mobile ?? $mobile,
            ], now()->addDays(30));

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => $user,
                'login_type' => 'doctor_reg',
                'doctor' => [
                    'id' => $doctor->id,
                    'name' => $doctor->name ?? 'Doctor',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Doctor reg login error', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login. Please try again or contact support.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function handleB2BLogin($mobile, $cacheKey)
    {
        $b2bQ = B2BUser::query()->where('is_active', true);
        $this->applyMobileMatch($b2bQ, 'mobile', $mobile);
        $b2b = $b2bQ->first();
        if (!$b2b) {
            return response()->json([
                'success' => false,
                'message' => 'No active B2B account found with this mobile number.',
            ], 404);
        }

        $userQ = User::withTrashed();
        $this->applyMobileMatch($userQ, 'mobile', $mobile);
        $user = $userQ->first();
        if (!$user) {
            $email = 'b2b_' . $mobile . '@carelix.com';
            $counter = 1;
            while (User::withTrashed()->where('email', $email)->exists()) {
                $email = 'b2b_' . $mobile . '_' . $counter . '@carelix.com';
                $counter++;
            }
            $user = User::create([
                'mobile' => $mobile,
                'f_name' => $b2b->name ?: 'B2B',
                'l_name' => '',
                'email' => $email,
                'password' => bcrypt(uniqid()),
                'role_id' => '0',
                'is_active' => 1,
            ]);
        } else {
            if ($user->trashed()) {
                $user->restore();
            }
            $user->f_name = $b2b->name ?: 'B2B';
            $user->l_name = '';
            $user->is_active = 1;
            $user->save();
        }

        $token = $user->createToken('mobile-app')->plainTextToken;
        Cache::forget($cacheKey);
        Cache::forget('login_type_' . $mobile);
        $b2bPayload = [
            'b2b_user_id' => $b2b->id,
            'name' => $b2b->name,
            'mobile' => $b2b->mobile,
            'company_name' => $b2b->company_name,
            'account_type' => $b2b->account_type,
            'portal_label' => $b2b->portalLabel(),
            'service_requirement' => $b2b->service_requirement,
            'bulk_requirement_qty' => $b2b->bulk_requirement_qty,
            'commission_percent' => $b2b->commission_percent,
            'chat_enabled' => (bool) $b2b->chat_enabled,
            'chat_group_name' => $b2b->chat_group_name,
        ];
        Cache::put('b2b_info_' . $user->id, $b2bPayload, now()->addDays(30));

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
            'login_type' => 'b2b',
            'b2b' => [
                'id' => $b2b->id,
                'name' => $b2b->name,
                'company_name' => $b2b->company_name,
                'mobile' => $b2b->mobile,
                'account_type' => $b2b->account_type,
                'portal_label' => $b2b->portalLabel(),
                'service_requirement' => $b2b->service_requirement,
                'bulk_requirement_qty' => $b2b->bulk_requirement_qty,
                'commission_percent' => $b2b->commission_percent,
                'chat_enabled' => (bool) $b2b->chat_enabled,
                'chat_group_name' => $b2b->chat_group_name,
            ],
        ]);
    }

    private function handleB2BReferenceLogin($mobile, $cacheKey)
    {
        $refQ = B2BReferenceUser::query()->where('is_active', true);
        $this->applyMobileMatch($refQ, 'mobile', $mobile);
        $ref = $refQ->first();
        if (! $ref) {
            return response()->json([
                'success' => false,
                'message' => 'No active B2B reference account found with this mobile number.',
            ], 404);
        }

        $userQ = User::withTrashed();
        $this->applyMobileMatch($userQ, 'mobile', $mobile);
        $user = $userQ->first();
        if (! $user) {
            $email = 'b2bref_' . $mobile . '@carelix.com';
            $counter = 1;
            while (User::withTrashed()->where('email', $email)->exists()) {
                $email = 'b2bref_' . $mobile . '_' . $counter . '@carelix.com';
                $counter++;
            }
            $user = User::create([
                'mobile' => $mobile,
                'f_name' => $ref->name ?: 'B2B Ref',
                'l_name' => '',
                'email' => $email,
                'password' => bcrypt(uniqid()),
                'role_id' => '0',
                'is_active' => 1,
            ]);
        } else {
            if ($user->trashed()) {
                $user->restore();
            }
            $user->f_name = $ref->name ?: 'B2B Ref';
            $user->l_name = '';
            $user->is_active = 1;
            $user->save();
        }

        $token = $user->createToken('mobile-app')->plainTextToken;
        Cache::forget($cacheKey);
        Cache::forget('login_type_' . $mobile);
        Cache::put('b2b_reference_info_' . $user->id, [
            'b2b_reference_user_id' => $ref->id,
            'name' => $ref->name,
            'mobile' => $ref->mobile,
        ], now()->addDays(30));

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
            'login_type' => 'b2b_reference',
            'b2b_reference' => [
                'id' => $ref->id,
                'name' => $ref->name,
                'mobile' => $ref->mobile,
            ],
        ]);
    }

    private function handleDoctorReferralLogin($mobile, $cacheKey)
    {
        $refQ = DoctorReferralUser::query()->where('is_active', true);
        $this->applyMobileMatch($refQ, 'mobile', $mobile);
        $ref = $refQ->first();
        if (! $ref) {
            return response()->json([
                'success' => false,
                'message' => 'No active doctor referral account found with this mobile number.',
            ], 404);
        }

        $userQ = User::withTrashed();
        $this->applyMobileMatch($userQ, 'mobile', $mobile);
        $user = $userQ->first();
        if (! $user) {
            $email = 'drref_' . $mobile . '@carelix.com';
            $counter = 1;
            while (User::withTrashed()->where('email', $email)->exists()) {
                $email = 'drref_' . $mobile . '_' . $counter . '@carelix.com';
                $counter++;
            }
            $user = User::create([
                'mobile' => $mobile,
                'f_name' => $ref->name ?: 'Doctor ref',
                'l_name' => '',
                'email' => $email,
                'password' => bcrypt(uniqid()),
                'role_id' => '0',
                'is_active' => 1,
            ]);
        } else {
            if ($user->trashed()) {
                $user->restore();
            }
            $user->f_name = $ref->name ?: 'Doctor ref';
            $user->l_name = '';
            $user->is_active = 1;
            $user->save();
        }

        $token = $user->createToken('mobile-app')->plainTextToken;
        Cache::forget($cacheKey);
        Cache::forget('login_type_' . $mobile);
        Cache::put('doctor_referral_info_' . $user->id, [
            'doctor_referral_user_id' => $ref->id,
            'name' => $ref->name,
            'mobile' => $ref->mobile,
            'commission_percent' => $ref->commission_percent !== null ? (string) $ref->commission_percent : null,
        ], now()->addDays(30));

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
            'login_type' => 'doctor_referral',
            'doctor_referral' => [
                'id' => $ref->id,
                'name' => $ref->name,
                'mobile' => $ref->mobile,
                'commission_percent' => $ref->commission_percent !== null ? (float) $ref->commission_percent : null,
            ],
        ]);
    }

    public function userInfo(Request $request)
    {
        $user = $request->user();
        $payload = ['user' => $user];

        if (Cache::get('doctor_reg_info_'.$user->id)) {
            $info = Cache::get('doctor_reg_info_'.$user->id);
            $payload['login_type'] = 'doctor_reg';
            $payload['doctor'] = [
                'id' => $info['doctor_request_id'] ?? null,
                'name' => $info['doctor_name'] ?? 'Doctor',
            ];
        } elseif (Cache::get('freelancer_info_'.$user->id)) {
            $payload['login_type'] = 'freelancer';
        } elseif (Cache::get('vendor_info_'.$user->id)) {
            $payload['login_type'] = 'vendor';
        } elseif (Cache::get('customer_info_'.$user->id)) {
            $payload['login_type'] = 'customer';
        } elseif (Cache::get('b2b_reference_info_'.$user->id)) {
            $payload['login_type'] = 'b2b_reference';
            $payload['b2b_reference'] = Cache::get('b2b_reference_info_'.$user->id);
        } elseif (Cache::get('doctor_referral_info_'.$user->id)) {
            $payload['login_type'] = 'doctor_referral';
            $payload['doctor_referral'] = Cache::get('doctor_referral_info_'.$user->id);
        } elseif (Cache::get('b2b_info_'.$user->id)) {
            $payload['login_type'] = 'b2b';
            $payload['b2b'] = Cache::get('b2b_info_'.$user->id);
        } elseif (Cache::get('insurer_info_'.$user->id)) {
            $payload['login_type'] = 'insurer';
            $payload['portal'] = Cache::get('insurer_info_'.$user->id);
        } elseif (Cache::get('broker_info_'.$user->id)) {
            $payload['login_type'] = 'broker';
            $payload['portal'] = Cache::get('broker_info_'.$user->id);
        } elseif (Cache::get('corporate_employee_info_'.$user->id)) {
            $payload['login_type'] = 'corporate_employee';
            $payload['portal'] = Cache::get('corporate_employee_info_'.$user->id);
        } else {
            $payload['login_type'] = 'employee';
        }

        return response()->json($payload);
    }

    public function saveExpoToken(Request $request)
    {
        $request->validate([
            'expo_token' => 'required|string',
        ]);
        $user = $request->user();
        $user->expo_token = $request->expo_token;
        $user->save();
        return response()->json(['message' => 'Expo token saved successfully', 'expo_token' => $user->expo_token]);
    }

    public function sendExpoNotification(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'title' => 'required|string',
            'description' => 'required|string',
        ]);
        $result = ExpoNotificationService::send($request->user_ids, $request->title, $request->description);
        return response()->json($result);
    }
}
