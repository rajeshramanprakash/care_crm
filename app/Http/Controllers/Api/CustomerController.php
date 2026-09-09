<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\PaymentInvoice;
use App\Models\ReceivedPayment;
use App\Models\Service;
use App\Models\Location;
use App\Models\OperationDeploymentDetails;
use App\Models\CustomerChatMessage;
use App\Models\CustomerChatCall;
use App\Models\ConsultationWebsiteBooking;
use App\Models\DeploymentLocationAttendance;
use App\Facades\UserAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\GeminiService;
use App\Support\DoctorCustomerChatRealtime;

class CustomerController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $mobileRaw = (string) ($user->mobile ?? '');
        $mobile = preg_replace('/\D/', '', $mobileRaw);
        if (strlen($mobile) >= 10) {
            $mobile = substr($mobile, -10);
        }

        $customerInfo = Cache::get('customer_info_' . $user->id);

        $lead = Lead::where('contact_no', $mobile)->first();
        $operationLead = OperationLead::where('contact_no', $mobile)->first();
        $crmCustomer = $operationLead ?? $lead;

        if ($crmCustomer) {
            $customer = $crmCustomer;
            $customerType = $operationLead ? 'operation_lead' : 'lead';
        } elseif ($customerInfo && ($customerInfo['customer_type'] ?? '') === 'consultation_booking') {
            $booking = ConsultationWebsiteBooking::query()
                ->whereTenDigitContact($mobile)
                ->orderByDesc('appointment_date')
                ->orderByDesc('id')
                ->first();
            $contactStored = ConsultationWebsiteBooking::normalizeContactToTenDigits(
                ($customerInfo['contact_no'] ?? $mobile) ?: $mobile
            ) ?: $mobile;
            if (!$booking && $mobile) {
                $booking = ConsultationWebsiteBooking::query()
                    ->whereTenDigitContact($contactStored)
                    ->orderByDesc('appointment_date')
                    ->orderByDesc('id')
                    ->first();
            }

            $customerType = 'consultation_booking';
            $customer = new \stdClass();
            $customer->id = -1;
            $customer->customer_name = $booking->customer_name
                ?? ($customerInfo['customer_name'] ?? 'Customer');
            $customer->contact_no = $booking
                ? (ConsultationWebsiteBooking::normalizeContactToTenDigits($booking->contact_no) ?: $mobile)
                : $contactStored;
            $customer->profile_image = null;
            $customer->mobile = $customer->contact_no;
            $customer->email = null;
            $customer->address = null;
            $customer->location = null;
            $customer->query = null;
            $customer->status = null;
            Cache::put('customer_info_' . $user->id, [
                'customer_id' => -1,
                'customer_type' => 'consultation_booking',
                'customer_name' => $customer->customer_name,
                'contact_no' => $customer->contact_no,
            ], now()->addDays(30));
        } elseif ($mobile !== '') {
            $bookingRecover = ConsultationWebsiteBooking::query()
                ->whereTenDigitContact($mobile)
                ->orderByDesc('appointment_date')
                ->orderByDesc('id')
                ->first();

            if ($bookingRecover) {
                $customerType = 'consultation_booking';
                $customer = new \stdClass();
                $customer->id = -1;
                $customer->customer_name = $bookingRecover->customer_name ?? 'Customer';
                $customer->contact_no = ConsultationWebsiteBooking::normalizeContactToTenDigits(
                    $bookingRecover->contact_no
                ) ?: $mobile;
                $customer->profile_image = null;
                $customer->mobile = $customer->contact_no;
                $customer->email = null;
                $customer->address = null;
                $customer->location = null;
                $customer->query = null;
                $customer->status = null;

                Cache::put('customer_info_' . $user->id, [
                    'customer_id' => -1,
                    'customer_type' => 'consultation_booking',
                    'customer_name' => $customer->customer_name,
                    'contact_no' => $customer->contact_no,
                ], now()->addDays(30));
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found',
                ], 404);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $customerId = (int) $customer->id;
        $contactNo = $customer->contact_no;

        // Get active leads with assignments
        $activeLeadsWithAssignments = collect();
        $operationLeadIds = OperationLead::where('contact_no', $contactNo)->pluck('id')->toArray();
        if ($customerType !== 'consultation_booking' && !in_array((int) $customerId, $operationLeadIds, true)) {
            $operationLeadIds[] = $customerId;
        }

        $deployments = OperationDeploymentDetails::whereIn('operation_lead_id', $operationLeadIds)
            ->where(function($query) {
                $query->whereNotNull('vendor_id')
                      ->orWhereNotNull('freelance_staff_id');
            })
            ->with(['vendor', 'freelanceStaff', 'operationLead'])
            ->get();

        foreach ($deployments as $deployment) {
            $lead = $deployment->operationLead;
            if (!$lead) continue;

            if ($deployment->vendor_id) {
                $vendor = $deployment->vendor;
                if ($vendor) {
                    $activeLeadsWithAssignments->push([
                        'lead_id' => $lead->id,
                        'lead_type' => 'operation_lead',
                        'date' => $lead->created_at ?? now(),
                        'query' => $lead->query ?? 'N/A',
                        'assignment_type' => 'vendor',
                        'assignment_id' => $vendor->id,
                        'name' => $vendor->name ?? $vendor->customer_name ?? 'Vendor',
                        'profile_image' => $vendor->profile_image ?? null,
                        'contact_no' => $vendor->contact_no ?? $vendor->mobile ?? null,
                        'mobile' => $vendor->mobile ?? $vendor->contact_no ?? null,
                        'customer_location_attendance_enabled' => (bool) ($lead->customer_location_attendance_enabled ?? false),
                    ]);
                }
            }

            if ($deployment->freelance_staff_id) {
                $freelancer = $deployment->freelanceStaff;
                if ($freelancer) {
                    $activeLeadsWithAssignments->push([
                        'lead_id' => $lead->id,
                        'lead_type' => 'operation_lead',
                        'date' => $lead->created_at ?? now(),
                        'query' => $lead->query ?? 'N/A',
                        'assignment_type' => 'freelancer',
                        'assignment_id' => $freelancer->id,
                        'name' => $freelancer->name ?? $freelancer->customer_name ?? 'Freelancer',
                        'profile_image' => $freelancer->profile_image ?? null,
                        'contact_no' => $freelancer->contact_no ?? $freelancer->mobile ?? null,
                        'mobile' => $freelancer->mobile ?? $freelancer->contact_no ?? null,
                        'customer_location_attendance_enabled' => (bool) ($lead->customer_location_attendance_enabled ?? false),
                    ]);
                }
            }
        }

        $today = now()->toDateString();
        $assignmentAttendanceMap = DeploymentLocationAttendance::whereIn('operation_lead_id', $operationLeadIds)
            ->whereDate('attendance_date', $today)
            ->get()
            ->keyBy(function ($row) {
                $type = $row->vendor_id ? 'vendor' : 'freelancer';
                $id = $row->vendor_id ?: $row->freelancer_id;

                return $row->operation_lead_id.'|'.$type.'|'.$id;
            });
        $activeLeadsWithAssignments = $activeLeadsWithAssignments->map(function ($assignment) use ($assignmentAttendanceMap) {
            $key = $assignment['lead_id'].'|'.$assignment['assignment_type'].'|'.$assignment['assignment_id'];
            $assignment['today_attendance'] = $assignmentAttendanceMap->get($key);

            return $assignment;
        });

        // Get active queries
        $activeStatuses = ['Active', 'In Progress', 'Ongoing', 'Active Deployment'];
        $activeLeads = Lead::where('contact_no', $contactNo)
            ->whereIn('status', $activeStatuses)
            ->orderBy('created_at', 'desc')
            ->get();

        $activeOperationLeads = OperationLead::where('contact_no', $contactNo)
            ->whereIn('status', $activeStatuses)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get payment invoices (for operation leads)
        $paymentInvoices = collect();
        if ($customerType === 'operation_lead') {
            $paymentInvoices = PaymentInvoice::where('operation_lead_id', $customerId)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Prepare customer data with all necessary fields including profile image URL
        $profileImage = $customer->profile_image ?? null;
        $profileImageUrl = null;
        if ($profileImage) {
            // If it's already a full URL, use it as is
            if (strpos($profileImage, 'http') === 0) {
                $profileImageUrl = $profileImage;
            } else {
                // Generate full URL using request's scheme and host
                $storagePath = Storage::disk('public')->url($profileImage);
                // If Storage::url() returns a relative path, prepend the request URL
                if (strpos($storagePath, 'http') !== 0) {
                    $scheme = $request->getScheme();
                    $host = $request->getHost();
                    $port = $request->getPort();
                    $baseUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
                    $profileImageUrl = $baseUrl . $storagePath;
                } else {
                    $profileImageUrl = $storagePath;
                }
            }
        }
        
        $customerData = [
            'id' => $customer->id === -1 ? null : $customer->id,
            'customer_name' => $customer->customer_name ?? 'Customer',
            'contact_no' => $customer->contact_no ?? null,
            'mobile' => $customer->mobile ?? $customer->contact_no ?? null,
            'email' => $customer->email ?? null,
            'profile_image' => $profileImage, // Store the path
            'profile_image_url' => $profileImageUrl, // Store the full URL
            'address' => $customer->address ?? null,
            'location' => $customer->location ?? null,
            'query' => $customer->query ?? null,
            'status' => $customer->status ?? null,
        ];

        $consultationWebsiteBookings = ConsultationWebsiteBooking::query()
            ->whereTenDigitContact($contactNo)
            ->with(['doctorRequest', 'consultationService'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ConsultationWebsiteBooking $b) => $b->toCustomerPortalArray())
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'customer' => $customerData,
            'customer_type' => $customerType,
            'customer_name' => $customer->customer_name ?? 'Customer',
            'active_leads_with_assignments' => $activeLeadsWithAssignments,
            'active_leads' => $activeLeads,
            'active_operation_leads' => $activeOperationLeads,
            'payment_invoices' => $paymentInvoices,
            'consultation_website_bookings' => $consultationWebsiteBookings,
        ]);
    }

    public function personalDetails(Request $request)
    {
        $user = $request->user();

        $mobileNormalized = preg_replace('/\D/', '', (string) ($user->mobile ?? ''));
        if (strlen($mobileNormalized) >= 10) {
            $mobileNormalized = substr($mobileNormalized, -10);
        }
        
        // Get customer info from cache
        $customerInfo = Cache::get('customer_info_' . $user->id);
        
        if (!$customerInfo) {
            // Fallback: try to find by mobile number
            $mobile = $mobileNormalized !== '' ? $mobileNormalized : null;
            if (!$mobile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile number not found'
                ], 404);
            }

            $lead = Lead::where('contact_no', $mobile)->first();
            $operationLead = OperationLead::where('contact_no', $mobile)->first();
            
            $customer = $lead ?? $operationLead;
            $customerType = $customer ? ($lead ? 'lead' : 'operation_lead') : null;

            if (!$customer && $mobile) {
                $booking = ConsultationWebsiteBooking::query()
                    ->whereTenDigitContact($mobile)
                    ->orderByDesc('appointment_date')
                    ->orderByDesc('id')
                    ->first();

                if ($booking) {
                    $customerType = 'consultation_booking';
                    $customer = new \stdClass();
                    $customer->id = -1;
                    $customer->customer_name = $booking->customer_name ?? 'Customer';
                    $customer->contact_no = ConsultationWebsiteBooking::normalizeContactToTenDigits($booking->contact_no) ?: $mobile;
                    $customer->mobile = $customer->contact_no;
                    $customer->email = null;
                    $customer->profile_image = null;
                    $customer->address = null;
                    $customer->location = null;
                    $customer->query = null;
                    $customer->status = null;
                }
            }
        } else {
            $customerId = $customerInfo['customer_id'];
            $customerType = $customerInfo['customer_type'];

            if ($customerType === 'consultation_booking') {
                $booking = ConsultationWebsiteBooking::query()
                    ->whereTenDigitContact((string) ($customerInfo['contact_no'] ?? $mobileNormalized))
                    ->orderByDesc('appointment_date')
                    ->orderByDesc('id')
                    ->first();

                $customer = new \stdClass();
                $customer->id = -1;
                $customer->customer_name = $booking->customer_name ?? ($customerInfo['customer_name'] ?? 'Customer');
                $customer->contact_no = $booking
                    ? (ConsultationWebsiteBooking::normalizeContactToTenDigits($booking->contact_no)
                        ?: ($customerInfo['contact_no'] ?? null))
                    : ($customerInfo['contact_no'] ?? '');
                $customer->mobile = $customer->contact_no;
                $customer->email = null;
                $customer->profile_image = null;
                $customer->address = null;
                $customer->location = null;
                $customer->query = null;
                $customer->status = null;
            } elseif ($customerType === 'operation_lead') {
                $customer = OperationLead::find($customerId);
            } else {
                $customer = Lead::find($customerId);
            }
        }

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        // Prepare customer data with profile image URL
        $profileImage = $customer->profile_image ?? null;
        $profileImageUrl = null;
        if ($profileImage) {
            if (strpos($profileImage, 'http') === 0) {
                $profileImageUrl = $profileImage;
            } else {
                // Generate full URL using request's scheme and host
                $storagePath = Storage::disk('public')->url($profileImage);
                // If Storage::url() returns a relative path, prepend the request URL
                if (strpos($storagePath, 'http') !== 0) {
                    $scheme = $request->getScheme();
                    $host = $request->getHost();
                    $port = $request->getPort();
                    $baseUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
                    $profileImageUrl = $baseUrl . $storagePath;
                } else {
                    $profileImageUrl = $storagePath;
                }
            }
        }
        
        $customerData = [
            'id' => isset($customer->id) && (int) $customer->id === -1 ? null : ($customer->id ?? null),
            'customer_name' => $customer->customer_name ?? 'Customer',
            'contact_no' => $customer->contact_no ?? null,
            'mobile' => $customer->mobile ?? $customer->contact_no ?? null,
            'email' => $customer->email ?? null,
            'profile_image' => $profileImage,
            'profile_image_url' => $profileImageUrl,
            'address' => $customer->address ?? null,
            'location' => $customer->location ?? null,
            'query' => $customer->query ?? null,
            'status' => $customer->status ?? null,
        ];

        return response()->json([
            'success' => true,
            'customer' => $customerData,
            'customer_type' => $customerType,
        ]);
    }

    public function updateProfileImage(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Get customer info from cache
        $customerInfo = Cache::get('customer_info_' . $user->id);
        
        if (!$customerInfo) {
            // Fallback: try to find by mobile number
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile number not found'
                ], 404);
            }

            $lead = Lead::where('contact_no', $mobile)->first();
            $operationLead = OperationLead::where('contact_no', $mobile)->first();
            
            $customer = $lead ?? $operationLead;
        } else {
            $customerId = $customerInfo['customer_id'];
            $customerType = $customerInfo['customer_type'];
            
            if ($customerType === 'operation_lead') {
                $customer = OperationLead::find($customerId);
            } else {
                $customer = Lead::find($customerId);
            }
        }

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($customer->profile_image) {
                $oldPath = $customer->profile_image;
                // Check if it's a full path or relative path
                if (strpos($oldPath, 'customer_profiles/') === 0 || strpos($oldPath, 'storage/') === 0) {
                    Storage::disk('public')->delete($oldPath);
                } else {
                    Storage::disk('public')->delete('customer_profiles/' . basename($oldPath));
                }
            }

            $path = $request->file('profile_image')->store('customer_profiles', 'public');
            $customer->profile_image = $path;
            $customer->save();

            // Generate full URL using request's scheme and host
            $storagePath = Storage::disk('public')->url($path);
            $imageUrl = $storagePath;
            // If Storage::url() returns a relative path, prepend the request URL
            if (strpos($storagePath, 'http') !== 0) {
                $scheme = $request->getScheme();
                $host = $request->getHost();
                $port = $request->getPort();
                $baseUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
                $imageUrl = $baseUrl . $storagePath;
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Profile image updated successfully',
                'profile_image' => $path, // Return the stored path
                'profile_image_url' => $imageUrl, // Return the full URL
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No image uploaded'
        ], 422);
    }

    public function chats(Request $request)
    {
        $user = $request->user();

        [$customer, $customerType] = $this->resolveCustomerModelForChats($request);
        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $customerInfo = Cache::get('customer_info_'.$user->id) ?? [
            'customer_id' => is_object($customer) && isset($customer->id) ? $customer->id : null,
            'customer_type' => $customerType,
            'contact_no' => $customer->contact_no ?? null,
        ];

        $contactNo = $customer->contact_no ?? null;
        if ($contactNo) {
            $contactNo = ConsultationWebsiteBooking::normalizeContactToTenDigits((string) $contactNo) ?: $contactNo;
        }
        $userTen = null;
        $mobileDigits = preg_replace('/\D/', '', (string) ($user->mobile ?? ''));
        if (strlen($mobileDigits) >= 10) {
            $userTen = substr($mobileDigits, -10);
        }
        $operationLeadIds = $this->operationLeadIdsForCustomerUser($request, $customerInfo, $customerType);

        $deployments = collect();
        if (! empty($operationLeadIds)) {
            $deployments = OperationDeploymentDetails::whereIn('operation_lead_id', $operationLeadIds)
                ->where(function ($query) {
                    $query->whereNotNull('vendor_id')
                        ->orWhereNotNull('freelance_staff_id');
                })
                ->with([
                    'vendor:id,name,customer_name,profile_image',
                    'freelanceStaff:id,name,customer_name,profile_image',
                ])
                ->get();
        }

        $sortedChats = collect();

        foreach ($deployments as $deployment) {
            if ($deployment->vendor_id) {
                $vendor = $deployment->vendor;
                if ($vendor) {
                    $sortedChats->push([
                        'id' => $vendor->id,
                        'chat_type' => 'vendor',
                        'name' => $vendor->name ?? $vendor->customer_name ?? 'Vendor',
                        'profile_image' => $vendor->profile_image ?? null,
                    ]);
                }
            }

            if ($deployment->freelance_staff_id) {
                $freelancer = $deployment->freelanceStaff;
                if ($freelancer) {
                    $sortedChats->push([
                        'id' => $freelancer->id,
                        'chat_type' => 'freelancer',
                        'name' => $freelancer->name ?? $freelancer->customer_name ?? 'Freelancer',
                        'profile_image' => $freelancer->profile_image ?? null,
                    ]);
                }
            }
        }

        $seenDoctorIds = [];
        if (! empty($operationLeadIds) || ! empty($contactNo) || ! empty($userTen)) {
            $doctorBookings = ConsultationWebsiteBooking::query()
                ->whereNotNull('doctor_request_id')
                ->where(function ($sub) use ($operationLeadIds, $contactNo, $userTen) {
                    $first = true;
                    if (! empty($operationLeadIds)) {
                        $sub->whereIn('operation_lead_id', $operationLeadIds);
                        $first = false;
                    }
                    if (! empty($contactNo)) {
                        if ($first) {
                            $sub->where(fn ($s) => $s->whereTenDigitContact($contactNo));
                        } else {
                            $sub->orWhere(fn ($s) => $s->whereTenDigitContact($contactNo));
                        }
                        $first = false;
                    }
                    if (! empty($userTen) && $userTen !== $contactNo) {
                        if ($first) {
                            $sub->where(fn ($s) => $s->whereTenDigitContact($userTen));
                        } else {
                            $sub->orWhere(fn ($s) => $s->whereTenDigitContact($userTen));
                        }
                    }
                })
                ->with(['doctorRequest:id,name,profile_image'])
                ->orderByDesc('created_at')
                ->get();
            foreach ($doctorBookings as $booking) {
                $dr = $booking->doctorRequest;
                if (!$dr || !$booking->doctor_request_id) {
                    continue;
                }
                if (isset($seenDoctorIds[$booking->doctor_request_id])) {
                    continue;
                }
                $seenDoctorIds[$booking->doctor_request_id] = true;
                $sortedChats->push([
                    'id' => $dr->id,
                    'chat_type' => 'doctor',
                    'name' => $dr->name ?? 'Doctor',
                    'profile_image' => $dr->profile_image ?? null,
                ]);
            }
        }

        $sortedChats = $sortedChats
            ->unique(fn ($c) => ($c['chat_type'] ?? '').':'.($c['id'] ?? ''))
            ->values()
            ->map(fn ($c) => $this->enrichApiChatEntry($c, $operationLeadIds))
            ->sort(function ($a, $b) {
                $aUnread = (int) ($a['unread_count'] ?? 0);
                $bUnread = (int) ($b['unread_count'] ?? 0);
                if ($aUnread > 0 && $bUnread === 0) {
                    return -1;
                }
                if ($aUnread === 0 && $bUnread > 0) {
                    return 1;
                }
                $aTime = isset($a['last_message']['created_at']) ? strtotime((string) $a['last_message']['created_at']) : 0;
                $bTime = isset($b['last_message']['created_at']) ? strtotime((string) $b['last_message']['created_at']) : 0;

                return $bTime <=> $aTime;
            })
            ->values();

        return response()->json([
            'success' => true,
            'chats' => $sortedChats,
            'customer_type' => $customerType,
        ]);
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<int>  $operationLeadIds
     * @return array<string, mixed>
     */
    private function enrichApiChatEntry(array $entry, array $operationLeadIds): array
    {
        if (empty($operationLeadIds)) {
            $entry['unread_count'] = 0;
            $entry['last_message'] = null;

            return $entry;
        }

        $chatType = (string) ($entry['chat_type'] ?? '');
        $chatId = (int) ($entry['id'] ?? 0);

        $unread = CustomerChatMessage::query()
            ->where('sender_type', $chatType)
            ->where('sender_id', $chatId)
            ->where('receiver_type', 'customer')
            ->whereIn('receiver_id', $operationLeadIds)
            ->where('is_read', false)
            ->count();

        $lastMsg = CustomerChatMessage::query()
            ->where(function ($q) use ($operationLeadIds, $chatType, $chatId) {
                $q->where('sender_type', 'customer')
                    ->whereIn('sender_id', $operationLeadIds)
                    ->where('receiver_type', $chatType)
                    ->where('receiver_id', $chatId);
            })
            ->orWhere(function ($q) use ($operationLeadIds, $chatType, $chatId) {
                $q->where('sender_type', $chatType)
                    ->where('sender_id', $chatId)
                    ->where('receiver_type', 'customer')
                    ->whereIn('receiver_id', $operationLeadIds);
            })
            ->orderByDesc('created_at')
            ->first();

        $entry['unread_count'] = $unread;
        $entry['last_message'] = $lastMsg ? [
            'id' => $lastMsg->id,
            'message' => $lastMsg->message,
            'attachment' => $lastMsg->attachment,
            'created_at' => $lastMsg->created_at?->toIso8601String() ?? (string) $lastMsg->created_at,
            'sender_type' => $lastMsg->sender_type,
        ] : null;

        return $entry;
    }

    public function getMessages(Request $request, $chatType, $chatId)
    {
        $user = $request->user();

        $customerInfo = $this->customerInfoForApiUser($request);
        if (! $customerInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $customerType = $customerInfo['customer_type'] ?? null;
        $operationLeadIds = $this->operationLeadIdsForCustomerUser($request, $customerInfo, $customerType);
        $openSchedule = [
            'allowed' => true,
            'user_message' => null,
            'window_start' => null,
            'window_end' => null,
            'next_window_start' => null,
        ];
        if (empty($operationLeadIds)) {
            return response()->json([
                'items' => [],
                'chat_schedule' => [
                    'allowed' => false,
                    'user_message' => 'No customer profile is linked to chat yet.',
                    'window_start' => null,
                    'window_end' => null,
                    'next_window_start' => null,
                ],
            ]);
        }

        // Query messages for all customer IDs with this contact number
        $query = CustomerChatMessage::where(function($q) use ($operationLeadIds, $chatType, $chatId) {
            $q->where('sender_type', 'customer')
              ->whereIn('sender_id', $operationLeadIds)
              ->where('receiver_type', $chatType)
              ->where('receiver_id', $chatId);
        })->orWhere(function($q) use ($operationLeadIds, $chatType, $chatId) {
            $q->where('sender_type', $chatType)
              ->where('sender_id', $chatId)
              ->where('receiver_type', 'customer')
              ->whereIn('receiver_id', $operationLeadIds);
        });

        // If last_message_id is provided, only fetch messages after it
        if ($request->has('last_message_id') && $request->last_message_id) {
            $query->where('id', '>', $request->last_message_id);
        }

        $messages = $query->with('repliedTo')->orderBy('created_at', 'asc')->get();

        // Get call history
        $calls = CustomerChatCall::where(function($q) use ($operationLeadIds, $chatType, $chatId) {
            $q->where('caller_type', 'customer')
              ->whereIn('caller_id', $operationLeadIds)
              ->where('receiver_type', $chatType)
              ->where('receiver_id', $chatId);
        })->orWhere(function($q) use ($operationLeadIds, $chatType, $chatId) {
            $q->where('caller_type', $chatType)
              ->where('caller_id', $chatId)
              ->where('receiver_type', 'customer')
              ->whereIn('receiver_id', $operationLeadIds);
        })->orderBy('created_at', 'asc')->get();

        // Combine messages and calls
        $combined = collect();
        
        foreach ($messages as $msg) {
            $combined->push([
                'type' => 'message',
                'id' => $msg->id,
                'created_at' => $msg->created_at,
                'data' => $msg
            ]);
        }
        
        foreach ($calls as $call) {
            $combined->push([
                'type' => 'call',
                'id' => 'call_' . $call->id,
                'created_at' => $call->call_started_at ?? $call->created_at,
                'data' => $call
            ]);
        }
        
        $sorted = $combined->sortBy('created_at')->values();

        $chatSchedule = $openSchedule;
        if ($chatType === 'doctor') {
            $primaryLeadId = (int) ($operationLeadIds[0] ?? 0);
            $chatSchedule = ConsultationWebsiteBooking::doctorCustomerChatScheduleStatus(
                (int) $chatId,
                $primaryLeadId
            );
        }

        return response()->json([
            'items' => $sorted,
            'chat_schedule' => $chatSchedule,
        ]);
    }

    public function sendMessage(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                \Log::error('Customer sendMessage: User not authenticated');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $customerInfo = $this->customerInfoForApiUser($request);
            if (! $customerInfo) {
                \Log::warning('Customer sendMessage: Could not resolve customer (cache + CRM/booking)', [
                    'user_id' => $user->id,
                    'user_mobile' => $user->mobile ?? null,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found',
                ], 404);
            }

            $customerId = $customerInfo['customer_id'];
            $contactNo = $customerInfo['contact_no'] ?? null;
            $customerType = $customerInfo['customer_type'] ?? null;

            \Log::info('Customer sendMessage: Request received', [
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'contact_no' => $contactNo,
                'receiver_type' => $request->receiver_type,
                'receiver_id' => $request->receiver_id,
                'has_message' => !empty(trim($request->message ?? '')),
                'has_attachment' => $request->hasFile('attachment'),
            ]);

            // Custom validation - allow empty message if attachment exists
            $hasMessage = !empty(trim($request->message ?? ''));
            $hasAttachment = $request->hasFile('attachment');
            
            if (!$hasMessage && !$hasAttachment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message or attachment is required'
                ], 422);
            }

            $validated = $request->validate([
                'receiver_type' => 'required|in:vendor,freelancer,doctor',
                'receiver_id' => 'required|integer',
                'message' => 'nullable|string',
                'attachment' => 'nullable|file|max:10240',
                'reply_to_id' => 'nullable|exists:customer_chat_messages,id'
            ]);
            
            // Ensure receiver_id is integer
            $receiverId = (int) $validated['receiver_id'];
            $receiverType = $validated['receiver_type'];

            $operationLeadIds = $this->operationLeadIdsForCustomerUser($request, $customerInfo, $customerType);

            $messageCustomerId = $this->primaryOperationLeadIdForMessaging(
                $request,
                $customerInfo,
                $customerType,
                $operationLeadIds
            );

            \Log::info('Customer sendMessage: Operation lead IDs', [
                'operation_lead_ids' => $operationLeadIds,
                'customer_id' => $customerId,
            ]);

            // Verify assignment
            $isAssigned = false;
            if ($receiverType === 'vendor') {
                $isAssigned = OperationDeploymentDetails::whereIn('operation_lead_id', $operationLeadIds)
                    ->where('vendor_id', $receiverId)
                    ->exists();
            } elseif ($receiverType === 'freelancer') {
                $isAssigned = OperationDeploymentDetails::whereIn('operation_lead_id', $operationLeadIds)
                    ->where('freelance_staff_id', $receiverId)
                    ->exists();
            } elseif ($receiverType === 'doctor' && $messageCustomerId) {
                $isAssigned = ConsultationWebsiteBooking::doctorHasBookedAnyLead($receiverId, (int) $messageCustomerId);
            }

            // Also check if there are existing messages
            $hasExistingMessages = false;
            if (!empty($operationLeadIds)) {
                $hasExistingMessages = CustomerChatMessage::where(function($q) use ($operationLeadIds, $receiverType, $receiverId) {
                    $q->where('sender_type', 'customer')
                      ->whereIn('sender_id', $operationLeadIds)
                      ->where('receiver_type', $receiverType)
                      ->where('receiver_id', $receiverId);
                })->orWhere(function($q) use ($operationLeadIds, $receiverType, $receiverId) {
                    $q->where('sender_type', $receiverType)
                      ->where('sender_id', $receiverId)
                      ->where('receiver_type', 'customer')
                      ->whereIn('receiver_id', $operationLeadIds);
                })->exists();
            }

            \Log::info('Customer sendMessage: Assignment check', [
                'is_assigned' => $isAssigned,
                'has_existing_messages' => $hasExistingMessages,
            ]);

            if (!$isAssigned && !$hasExistingMessages) {
                \Log::warning('Customer sendMessage: Not assigned and no existing messages', [
                    'customer_id' => $customerId,
                    'contact_no' => $contactNo,
                    'operation_lead_ids' => $operationLeadIds,
                    'receiver_type' => $receiverType,
                    'receiver_id' => $receiverId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Not assigned to you'
                ], 403);
            }

            if ($receiverType === 'doctor' && $messageCustomerId) {
                $chatSchedule = ConsultationWebsiteBooking::doctorCustomerChatScheduleStatus(
                    $receiverId,
                    (int) $messageCustomerId
                );
                if (! $chatSchedule['allowed']) {
                    return response()->json([
                        'success' => false,
                        'message' => $chatSchedule['user_message'] ?? 'Chat is not available outside your appointment window.',
                        'chat_schedule' => $chatSchedule,
                    ], 403);
                }
            }

            if (!$messageCustomerId) {
                \Log::error('Customer sendMessage: Unable to determine customer ID', [
                    'customer_id' => $customerId,
                    'operation_lead_ids' => $operationLeadIds,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to determine customer ID'
                ], 400);
            }
            
            \Log::info('Customer sendMessage: Using customer ID for message', [
                'original_customer_id' => $customerId,
                'message_customer_id' => $messageCustomerId,
                'operation_lead_ids' => $operationLeadIds,
            ]);

            $data = [
                'sender_type' => 'customer',
                'sender_id' => (int) $messageCustomerId,
                'receiver_type' => $receiverType,
                'receiver_id' => $receiverId,
                'message' => $hasMessage ? trim($request->message) : null,
                'is_read' => false,
            ];

            if ($request->has('reply_to_id') && $request->reply_to_id) {
                $data['reply_to_id'] = $request->reply_to_id;
            }

            if ($hasAttachment) {
                try {
                    $path = $request->file('attachment')->store('customer_chat_attachments', 'public');
                    $data['attachment'] = $path;
                    $data['attachment_type'] = $request->file('attachment')->getMimeType();
                    \Log::info('Customer sendMessage: Attachment saved', [
                        'path' => $path,
                        'type' => $data['attachment_type'],
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Customer sendMessage: Error saving attachment', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to save attachment: ' . $e->getMessage()
                    ], 500);
                }
            }

            \Log::info('Customer sendMessage: Creating message', $data);

            try {
                $message = CustomerChatMessage::create($data);
                
                // Refresh from database to ensure it was saved
                $message->refresh();
                
                // Verify message was saved
                $savedMessage = CustomerChatMessage::find($message->id);
                if (!$savedMessage) {
                    \Log::error('Customer sendMessage: Message was not saved to database', [
                        'message_id' => $message->id,
                        'data' => $data,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Message was not saved to database'
                    ], 500);
                }
                
                \Log::info('Customer sendMessage: Message created and verified successfully', [
                    'message_id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'sender_id' => $message->sender_id,
                    'receiver_type' => $message->receiver_type,
                    'receiver_id' => $message->receiver_id,
                    'has_message' => !empty($message->message),
                    'has_attachment' => !empty($message->attachment),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'id' => $message->id,
                    'data' => $message,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                \Log::error('Customer sendMessage: Database error creating message', [
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'data' => $data,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ], 500);
            } catch (\Exception $e) {
                \Log::error('Customer sendMessage: Error creating message', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'data' => $data,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save message: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Customer sendMessage: Validation error', [
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Customer sendMessage: Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markAsRead(Request $request, $chatType, $chatId)
    {
        $customerInfo = $this->customerInfoForApiUser($request);
        if (! $customerInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $customerType = $customerInfo['customer_type'] ?? null;
        $operationLeadIds = $this->operationLeadIdsForCustomerUser($request, $customerInfo, $customerType);
        if (empty($operationLeadIds)) {
            return response()->json([
                'success' => true,
            ]);
        }

        \App\Models\CustomerChatMessage::where('sender_type', $chatType)
            ->where('sender_id', $chatId)
            ->where('receiver_type', 'customer')
            ->whereIn('receiver_id', $operationLeadIds)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($chatType === 'doctor') {
            $primaryLead = $this->primaryOperationLeadIdForMessaging($request, $customerInfo, $customerType, $operationLeadIds);
            if ($primaryLead) {
                DoctorCustomerChatRealtime::broadcastUnreadRefresh((int) $chatId, (int) $primaryLead, 'mark_read');
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function paymentDetails(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get customer info from cache
            $customerInfo = Cache::get('customer_info_' . $user->id);
            
            $customer = null;
            $contactNo = null;
            $customerId = null;
            $customerType = null;
            
            if (!$customerInfo) {
                // Fallback: try to find by mobile number
                $mobile = $user->mobile ?? null;
                if (!$mobile) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile number not found'
                    ], 404);
                }

                // Try to find as operation lead first
                $customer = OperationLead::where('contact_no', $mobile)->first();
                if ($customer) {
                    $customerType = 'operation_lead';
                    $customerId = $customer->id;
                    $contactNo = $customer->contact_no;
                } else {
                    // Check if it's a regular lead
                    $lead = Lead::where('contact_no', $mobile)->first();
                    if ($lead) {
                        // Even if it's a regular lead, check if there are OperationLeads with same contact_no
                        // This handles cases where customer might have been converted to operation lead
                        $operationLead = OperationLead::where('contact_no', $mobile)->first();
                        if ($operationLead) {
                            // Found operation lead with same contact_no, use it for payment details
                            $customer = $operationLead;
                            $customerType = 'operation_lead';
                            $customerId = $operationLead->id;
                            $contactNo = $operationLead->contact_no;
                        } else {
                            // No operation lead found, payment details not available
                            return response()->json([
                                'success' => false,
                                'message' => 'Payment details are only available for operation leads'
                            ], 404);
                        }
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Customer not found'
                        ], 404);
                    }
                }
            } else {
                $customerId = $customerInfo['customer_id'];
                $customerType = $customerInfo['customer_type'];
                
                if ($customerType === 'operation_lead') {
                    $customer = OperationLead::find($customerId);
                    if ($customer) {
                        $contactNo = $customer->contact_no;
                    }
                } else {
                    // Even if stored as 'lead', check if there are OperationLeads with same contact_no
                    // This handles cases where customer might have been converted to operation lead
                    $lead = Lead::find($customerId);
                    if ($lead && $lead->contact_no) {
                        $contactNo = $lead->contact_no;
                        // Check if there are any OperationLeads with this contact_no
                        $operationLead = OperationLead::where('contact_no', $contactNo)->first();
                        if ($operationLead) {
                            // Found operation lead with same contact_no, use it for payment details
                            $customer = $operationLead;
                            $customerType = 'operation_lead';
                            $customerId = $operationLead->id;
                            // Update cache with correct customer type
                            $customerInfo['customer_type'] = 'operation_lead';
                            $customerInfo['customer_id'] = $operationLead->id;
                            Cache::put('customer_info_' . $user->id, $customerInfo, now()->addDays(30));
                        } else {
                            // No operation lead found, payment details not available
                            return response()->json([
                                'success' => false,
                                'message' => 'Payment details are only available for operation leads'
                            ], 404);
                        }
                    } else {
                        // Try to find by mobile number from user
                        $mobile = $user->mobile ?? null;
                        if ($mobile) {
                            $operationLead = OperationLead::where('contact_no', $mobile)->first();
                            if ($operationLead) {
                                $customer = $operationLead;
                                $customerType = 'operation_lead';
                                $customerId = $operationLead->id;
                                $contactNo = $operationLead->contact_no;
                                // Update cache with correct customer type
                                $customerInfo['customer_type'] = 'operation_lead';
                                $customerInfo['customer_id'] = $operationLead->id;
                                Cache::put('customer_info_' . $user->id, $customerInfo, now()->addDays(30));
                            } else {
                                // No operation lead found, payment details not available
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Payment details are only available for operation leads'
                                ], 404);
                            }
                        } else {
                            // No contact_no found, payment details not available
                            return response()->json([
                                'success' => false,
                                'message' => 'Payment details are only available for operation leads'
                            ], 404);
                        }
                    }
                }
            }
            
            if (!$customer || !$contactNo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operation lead not found'
                ], 404);
            }

            // Get all OperationLead IDs for this customer (by contact_no) - same as web version
            $operationLeadIds = OperationLead::where('contact_no', $contactNo)->pluck('id')->toArray();
            
            // Also check if customerId itself is an OperationLead ID
            if (!in_array($customerId, $operationLeadIds)) {
                $operationLeadIds[] = $customerId;
            }

            // Get all payment invoices for this customer (all operation leads with same contact_no)
            $paymentInvoices = PaymentInvoice::whereIn('operation_lead_id', $operationLeadIds)
                ->with(['operationLead', 'receivedPayments'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate totals - same logic as web version
            $totalInvoiceAmount = $paymentInvoices->sum('payment_amount');
            $totalReceivedAmount = 0;
            $totalOutstandingAmount = 0;
            $totalAdvanceAmount = 0;

            foreach ($paymentInvoices as $invoice) {
                $received = $invoice->receivedPayments->sum('amount');
                $totalReceivedAmount += $received;
                $outstanding = $invoice->payment_amount - $received;
                $totalOutstandingAmount += $outstanding;
                
                // If received amount is more than invoice amount, it's advance
                if ($received > $invoice->payment_amount) {
                    $totalAdvanceAmount += ($received - $invoice->payment_amount);
                }
            }

            // Get all received payments for history (by operation_lead_id, not just payment_invoice_id)
            $receivedPayments = ReceivedPayment::whereIn('operation_lead_id', $operationLeadIds)
                ->with(['paymentInvoice', 'operationLead'])
                ->orderBy('received_date', 'desc')
                ->get();

            // Format invoices with relationships properly
            $formattedInvoices = $paymentInvoices->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_id' => $invoice->invoice_id,
                    'operation_lead_id' => $invoice->operation_lead_id,
                    'from_date' => $invoice->from_date ? $invoice->from_date->toDateTimeString() : null,
                    'to_date' => $invoice->to_date ? $invoice->to_date->toDateTimeString() : null,
                    'payment_amount' => (float) $invoice->payment_amount,
                    'work_days' => $invoice->work_days ?? 0,
                    'remark' => $invoice->remark,
                    'is_received' => $invoice->is_received ?? false,
                    'created_at' => $invoice->created_at ? $invoice->created_at->toDateTimeString() : null,
                    'updated_at' => $invoice->updated_at ? $invoice->updated_at->toDateTimeString() : null,
                    'received_payments' => $invoice->receivedPayments->map(function($payment) {
                        return [
                            'id' => $payment->id,
                            'payment_invoice_id' => $payment->payment_invoice_id,
                            'operation_lead_id' => $payment->operation_lead_id,
                            'amount' => (float) $payment->amount,
                            'received_date' => $payment->received_date ? $payment->received_date->toDateTimeString() : null,
                            'utr_number' => $payment->utr_number,
                            'screenshot' => $payment->screenshot,
                            'remark' => $payment->remark,
                            'created_at' => $payment->created_at ? $payment->created_at->toDateTimeString() : null,
                            'updated_at' => $payment->updated_at ? $payment->updated_at->toDateTimeString() : null,
                        ];
                    })->toArray(),
                ];
            })->toArray();

            // Format received payments with relationships properly
            $formattedPayments = $receivedPayments->map(function($payment) use ($request) {
                $screenshotUrl = null;
                if ($payment->screenshot) {
                    try {
                        // Check if file exists
                        $fileExists = Storage::disk('public')->exists($payment->screenshot);
                        
                        if ($fileExists) {
                            // Generate full URL for screenshot - ensure correct host (not localhost)
                            $storagePath = Storage::disk('public')->url($payment->screenshot);
                            
                            // Always use request host to avoid localhost issue
                            $scheme = $request->getScheme();
                            $host = $request->getHost();
                            $port = $request->getPort();
                            $baseUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
                            
                            // Extract just the path part
                            if (strpos($storagePath, 'http') === 0) {
                                $path = parse_url($storagePath, PHP_URL_PATH);
                            } else {
                                $path = $storagePath;
                            }
                            
                            // Ensure path starts with /
                            if (strpos($path, '/') !== 0) {
                                $path = '/' . $path;
                            }
                            
                            $screenshotUrl = $baseUrl . $path;
                        } else {
                            Log::warning('Screenshot file does not exist', [
                                'payment_id' => $payment->id,
                                'screenshot_path' => $payment->screenshot
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Error generating screenshot URL', [
                            'payment_id' => $payment->id,
                            'screenshot_path' => $payment->screenshot,
                            'error' => $e->getMessage()
                        ]);
                        // Fallback: construct URL manually using request host
                        $scheme = $request->getScheme();
                        $host = $request->getHost();
                        $port = $request->getPort();
                        $baseUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
                        $screenshotUrl = $baseUrl . '/storage/' . $payment->screenshot;
                    }
                }
                
                return [
                    'id' => $payment->id,
                    'payment_invoice_id' => $payment->payment_invoice_id,
                    'operation_lead_id' => $payment->operation_lead_id,
                    'amount' => (float) $payment->amount,
                    'received_date' => $payment->received_date ? $payment->received_date->toDateTimeString() : null,
                    'utr_number' => $payment->utr_number,
                    'screenshot' => $payment->screenshot, // Keep path for reference
                    'screenshot_url' => $screenshotUrl, // Full URL for display
                    'remark' => $payment->remark,
                    'created_at' => $payment->created_at ? $payment->created_at->toDateTimeString() : null,
                    'updated_at' => $payment->updated_at ? $payment->updated_at->toDateTimeString() : null,
                    'payment_invoice' => $payment->paymentInvoice ? [
                        'id' => $payment->paymentInvoice->id,
                        'invoice_id' => $payment->paymentInvoice->invoice_id,
                        'payment_amount' => (float) $payment->paymentInvoice->payment_amount,
                    ] : null,
                ];
            })->toArray();

            // Log for debugging
            Log::info('Payment Details API Response', [
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'contact_no' => $contactNo,
                'operation_lead_ids' => $operationLeadIds,
                'invoices_count' => $paymentInvoices->count(),
                'payments_count' => $receivedPayments->count(),
                'total_invoice_amount' => $totalInvoiceAmount,
                'total_received_amount' => $totalReceivedAmount,
                'total_advance_amount' => $totalAdvanceAmount,
                'total_outstanding_amount' => $totalOutstandingAmount,
            ]);

            return response()->json([
                'success' => true,
                'payment_invoices' => $formattedInvoices,
                'received_payments' => $formattedPayments,
                'total_invoice_amount' => (float) $totalInvoiceAmount,
                'total_received_amount' => (float) $totalReceivedAmount,
                'total_advance_amount' => (float) $totalAdvanceAmount,
                'total_outstanding_amount' => (float) $totalOutstandingAmount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in paymentDetails', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? 'N/A',
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading payment details. Please try again.'
            ], 500);
        }
    }

    public function submitServiceRequest(Request $request)
    {
        $user = $request->user();
        
        // Get customer info from cache
        $customerInfo = Cache::get('customer_info_' . $user->id);
        
        if (!$customerInfo) {
            // Fallback: try to find by mobile number
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile number not found'
                ], 404);
            }

            $customer = \App\Models\Lead::where('contact_no', $mobile)
                ->orWhere('contact_no', $mobile)
                ->first();
            
            if (!$customer) {
                $customer = \App\Models\OperationLead::where('contact_no', $mobile)->first();
            }
        } else {
            $customerId = $customerInfo['customer_id'];
            $customerType = $customerInfo['customer_type'];
            
            if ($customerType === 'operation_lead') {
                $customer = \App\Models\OperationLead::find($customerId);
            } else {
                $customer = \App\Models\Lead::find($customerId);
            }
        }
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        // Validate request
        $request->validate([
            'query' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'patient_name' => 'required|string|max:255',
            'patient_gender' => 'required|in:male,female,other',
            'age' => 'required|integer|min:0|max:150',
            'contact_type' => 'required|in:call,whatsapp,email,visit,other',
            'contact_no' => 'required|string|max:10|min:10',
            'location' => 'required|string|max:255',
        ]);

        // Assign to sales executive using UserAssignment (role_id 2 for sales, lead_type 'web')
        $getUser = UserAssignment::getAssigningUser(2, null, 'web');
        
        if (!$getUser) {
            return response()->json([
                'success' => false,
                'message' => 'No sales executive available to assign this lead'
            ], 500);
        }

        // Create lead
        $lead = \App\Models\Lead::create([
            'date' => now(),
            'executive' => $getUser->id,
            'customer_name' => $request->input('customer_name'),
            'contact_no' => $request->input('contact_no'),
            'patient_name' => $request->input('patient_name'),
            'patient_gender' => $request->input('patient_gender'),
            'age' => $request->input('age'),
            'contact_type' => $request->input('contact_type'),
            'location' => $request->input('location'),
            'query' => $request->input('query'),
            'lead_source' => 'app',
            'status' => 'follow-up',
            'stage' => 'active',
        ]);

        // Add executive to WhatsApp group if contact number exists
        if ($request->input('contact_no')) {
            $number = $request->input('contact_no');
            $execId = $getUser->id;
            $group = \App\Models\WhatsappMsgGroup::where('whatsapp_number', $number)->first();
            if ($group) {
                $ids = array_filter(explode(',', $group->executive_ids));
                if (!in_array($execId, $ids)) {
                    $ids[] = $execId;
                    $group->executive_ids = implode(',', $ids);
                    $group->save();
                }
            } else {
                \App\Models\WhatsappMsgGroup::create([
                    'whatsapp_number' => $number,
                    'executive_ids' => $execId,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Service request submitted successfully',
            'lead_id' => $lead->id
        ]);
    }

    public function submitAttendanceLocation(Request $request)
    {
        $user = $request->user();
        $customerInfo = $this->customerInfoForApiUser($request);
        if (! $customerInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $validated = $request->validate([
            'operation_lead_id' => 'required|integer|exists:operation_leads,id',
            'assignment_type' => 'required|in:vendor,freelancer',
            'assignment_id' => 'required|integer|min:1',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $operationLeadIds = $this->operationLeadIdsForCustomerUser(
            $request,
            $customerInfo,
            $customerInfo['customer_type'] ?? null
        );
        if (! in_array((int) $validated['operation_lead_id'], $operationLeadIds, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid operation lead for this customer',
            ], 403);
        }

        $assignmentQuery = OperationDeploymentDetails::where('operation_lead_id', $validated['operation_lead_id']);
        if ($validated['assignment_type'] === 'vendor') {
            $assignmentQuery->where('vendor_id', $validated['assignment_id']);
        } else {
            $assignmentQuery->where('freelance_staff_id', $validated['assignment_id']);
        }
        if (! $assignmentQuery->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found for this lead',
            ], 404);
        }

        $attendance = DeploymentLocationAttendance::firstOrNew([
            'operation_lead_id' => $validated['operation_lead_id'],
            'vendor_id' => $validated['assignment_type'] === 'vendor' ? $validated['assignment_id'] : null,
            'freelancer_id' => $validated['assignment_type'] === 'freelancer' ? $validated['assignment_id'] : null,
            'attendance_date' => now()->toDateString(),
        ]);

        $lead = OperationLead::find($validated['operation_lead_id']);
        if (! $lead || ! $lead->customer_location_attendance_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Pehle "Attendance mark" enable karein (Your Requirement card).',
            ], 422);
        }

        $providerLocationCaptured = $validated['assignment_type'] === 'vendor'
            ? (bool) $attendance->vendor_location_captured_at
            : (bool) ($attendance->freelancer_location_captured_at && $attendance->freelancer_selfie_path);

        if (! $providerLocationCaptured) {
            return response()->json([
                'success' => false,
                'message' => $validated['assignment_type'] === 'vendor'
                    ? 'Vendor ne location share nahi ki.'
                    : 'Freelancer ne location + selfie complete nahi kiya.',
            ], 422);
        }

        $attendance->customer_latitude = $validated['latitude'];
        $attendance->customer_longitude = $validated['longitude'];
        $attendance->customer_location_captured_at = now();

        if ($providerLocationCaptured) {
            $providerLat = $validated['assignment_type'] === 'vendor'
                ? (float) $attendance->vendor_latitude
                : (float) $attendance->freelancer_latitude;
            $providerLng = $validated['assignment_type'] === 'vendor'
                ? (float) $attendance->vendor_longitude
                : (float) $attendance->freelancer_longitude;
            $distance = $this->distanceInMeters(
                (float) $attendance->customer_latitude,
                (float) $attendance->customer_longitude,
                $providerLat,
                $providerLng
            );
            $attendance->distance_meters = $distance;
            $attendance->is_location_matched = $distance <= 250;
            if ($attendance->is_location_matched) {
                $attendance->attendance_marked_at = now();
                $attendance->attendance_status = 'present';
            } else {
                $attendance->attendance_status = 'location_not_matched';
            }
        } elseif (! $attendance->attendance_marked_at) {
            $attendance->attendance_status = 'waiting_for_' . $validated['assignment_type'] . '_location';
        }

        $attendance->save();

        return response()->json([
            'success' => true,
            'message' => $attendance->attendance_marked_at
                ? 'Attendance marked successfully'
                : 'Customer location captured successfully.',
            'attendance' => [
                'customer_location_shared' => (bool) $attendance->customer_location_captured_at,
                'vendor_location_shared' => (bool) $attendance->vendor_location_captured_at,
                'freelancer_location_shared' => (bool) $attendance->freelancer_location_captured_at,
                'attendance_marked' => (bool) $attendance->attendance_marked_at,
                'attendance_status' => $attendance->attendance_status,
                'is_location_matched' => (bool) $attendance->is_location_matched,
                'distance_meters' => $attendance->distance_meters,
            ],
        ]);
    }

    public function setLocationAttendanceEnabled(Request $request)
    {
        $customerInfo = $this->customerInfoForApiUser($request);
        if (! $customerInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $validated = $request->validate([
            'operation_lead_id' => 'required|integer|exists:operation_leads,id',
            'enabled' => 'required|boolean',
        ]);

        $operationLeadIds = $this->operationLeadIdsForCustomerUser(
            $request,
            $customerInfo,
            $customerInfo['customer_type'] ?? null
        );
        if (! in_array((int) $validated['operation_lead_id'], $operationLeadIds, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid operation lead for this customer',
            ], 403);
        }

        $lead = OperationLead::findOrFail($validated['operation_lead_id']);
        $lead->customer_location_attendance_enabled = $validated['enabled'];
        $lead->save();

        return response()->json([
            'success' => true,
            'message' => $validated['enabled']
                ? 'Location attendance enabled.'
                : 'Location attendance disabled.',
            'customer_location_attendance_enabled' => (bool) $lead->customer_location_attendance_enabled,
        ]);
    }

    private function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }

    public function getServices()
    {
        $services = Service::orderBy('name', 'asc')->get();
        return response()->json([
            'success' => true,
            'services' => $services,
        ]);
    }

    public function getLocations()
    {
        $locations = Location::orderBy('name', 'asc')->get();
        return response()->json([
            'success' => true,
            'locations' => $locations,
        ]);
    }

    /**
     * View or download a payment invoice (same as customer web portal).
     * Supports Bearer auth or ?token= for mobile WebBrowser / FileSystem download.
     */
    public function showPaymentInvoice(Request $request, $id)
    {
        $user = null;
        $token = $request->query('token');
        if ($token) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                ], 401);
            }
        } else {
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }
        }

        $customerInfo = Cache::get('customer_info_'.$user->id);
        if (! $customerInfo) {
            $mobile = preg_replace('/\D/', '', (string) ($user->mobile ?? ''));
            if (strlen($mobile) >= 10) {
                $mobile = substr($mobile, -10);
            }
            if ($mobile === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer information not found',
                ], 404);
            }
            $operationLead = OperationLead::where('contact_no', $mobile)->first();
            if (! $operationLead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer information not found',
                ], 404);
            }
            $customerInfo = [
                'customer_id' => (int) $operationLead->id,
                'customer_type' => 'operation_lead',
                'contact_no' => $operationLead->contact_no,
            ];
        }

        $customerType = $customerInfo['customer_type'] ?? null;
        $operationLeadIds = $this->operationLeadIdsForCustomerUser($request, $customerInfo, $customerType);
        if ($operationLeadIds === []) {
            return response()->json([
                'success' => false,
                'message' => 'No invoices found',
            ], 404);
        }

        $invoice = PaymentInvoice::with(['operationLead', 'receivedPayments'])
            ->whereIn('operation_lead_id', $operationLeadIds)
            ->find((int) $id);

        if ($invoice === null) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        $totalReceived = (float) $invoice->receivedPayments->sum('amount');
        $paymentAmount = (float) $invoice->payment_amount;
        if ($totalReceived >= $paymentAmount) {
            $status = 'paid';
        } elseif ($totalReceived > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'unpaid';
        }

        if ($request->query('download') === '1') {
            $forPdf = true;
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'admin.operation_leads.payment_invoice_show',
                compact('invoice', 'totalReceived', 'status', 'forPdf')
            );

            return $pdf->download(
                'invoice-'.preg_replace('/[^a-zA-Z0-9\-_]/', '-', (string) $invoice->invoice_id).'.pdf'
            );
        }

        return view('admin.operation_leads.payment_invoice_show', compact('invoice', 'totalReceived', 'status'));
    }

    public function viewScreenshot(Request $request, $paymentId)
    {
        Log::info('Screenshot request received', [
            'payment_id' => $paymentId,
            'has_token' => $request->has('token'),
            'token_preview' => $request->has('token') ? substr($request->query('token'), 0, 20) . '...' : null
        ]);
        
        // Support token authentication via query parameter for Image component
        $token = $request->query('token');
        $user = null;
        
        if ($token) {
            try {
                // Authenticate using token from query parameter
                $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                if ($accessToken && $accessToken->tokenable) {
                    $user = $accessToken->tokenable;
                    Log::info('User authenticated via token query parameter', ['user_id' => $user->id]);
                } else {
                    Log::warning('Invalid token provided', ['token_preview' => substr($token, 0, 20) . '...']);
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid token'
                    ], 401);
                }
            } catch (\Exception $e) {
                Log::error('Token authentication error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed'
                ], 401);
            }
        } else {
            // Use standard authentication (Bearer token from header)
            $user = $request->user();
            if (!$user) {
                Log::warning('No user authenticated for screenshot request');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
        }
        
        if (!$user) {
            Log::error('User is null after authentication attempt');
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }
        
        // Get customer info from cache (set during login)
        $customerInfo = Cache::get('customer_info_' . $user->id);
        
        if (!$customerInfo) {
            // Fallback: try to find by mobile number
            $mobile = $user->mobile ?? null;
            if (!$mobile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer information not found'
                ], 404);
            }

            // Find customer in Lead or OperationLead
            $lead = Lead::where('contact_no', $mobile)->first();
            $operationLead = OperationLead::where('contact_no', $mobile)->first();
            
            $customer = $lead ?? $operationLead;
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer information not found'
                ], 404);
            }
            $customerInfo = [
                'contact_no' => $mobile,
                'customer_id' => $customer->id,
                'customer_type' => $lead ? 'lead' : 'operation_lead'
            ];
        }

        // Find the payment record
        $payment = ReceivedPayment::find($paymentId);
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        // Verify the payment belongs to this customer
        $operationLeadIds = OperationLead::where('contact_no', $customerInfo['contact_no'])
            ->pluck('id')
            ->toArray();

        if (!in_array($payment->operation_lead_id, $operationLeadIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this payment'
            ], 403);
        }

        if (!$payment->screenshot) {
            return response()->json([
                'success' => false,
                'message' => 'No screenshot found for this payment'
            ], 404);
        }

        $path = storage_path('app/public/' . $payment->screenshot);

        if (!file_exists($path)) {
            Log::warning('Screenshot file not found', [
                'payment_id' => $paymentId,
                'screenshot_path' => $payment->screenshot,
                'full_path' => $path
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Screenshot file not found on server'
            ], 404);
        }

        // Determine content type based on file extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $contentType = 'image/jpeg'; // default
        if ($extension === 'png') {
            $contentType = 'image/png';
        } elseif ($extension === 'gif') {
            $contentType = 'image/gif';
        } elseif ($extension === 'webp') {
            $contentType = 'image/webp';
        }

        // Return the file with appropriate headers for React Native Image component
        // Note: React Native Image component can't send auth headers, so we verify auth via token query param
        Log::info('Serving screenshot file', [
            'payment_id' => $paymentId,
            'file_path' => $path,
            'file_exists' => file_exists($path),
            'file_size' => file_exists($path) ? filesize($path) : 0,
            'content_type' => $contentType
        ]);
        
        // Use response()->file() which properly handles binary files for React Native
        // This ensures the file is served as pure binary without any encoding
        return response()->file($path, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=3600',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }

    /**
     * Session cache from login, else same resolution as chats/getMessages (mobile → Lead/OperationLead/consultation booking).
     * Repopulates cache for 30 days so send/mark-read work after cache flush or cold devices.
     *
     * @return array<string, mixed>|null
     */
    private function customerInfoForApiUser(Request $request): ?array
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $cached = Cache::get('customer_info_'.$user->id);
        if ($cached) {
            return $cached;
        }

        [$c, $t] = $this->resolveCustomerModelForChats($request);
        if (! $c) {
            return null;
        }

        $contactNo = $c->contact_no ?? null;
        if ($contactNo === null || $contactNo === '') {
            $mobile = preg_replace('/\D/', '', (string) ($user->mobile ?? ''));
            if (strlen($mobile) >= 10) {
                $contactNo = substr($mobile, -10);
            }
        }
        if ($contactNo === null || $contactNo === '') {
            return null;
        }

        $normalized = ConsultationWebsiteBooking::normalizeContactToTenDigits((string) $contactNo) ?: $contactNo;

        $info = [
            'customer_id' => is_object($c) && isset($c->id) ? $c->id : -1,
            'customer_type' => $t,
            'contact_no' => $normalized,
        ];

        Cache::put('customer_info_'.$user->id, $info, now()->addDays(30));

        return $info;
    }

    /**
     * Resolve CRM / booking-only customer for chat list (same idea as dashboard).
     *
     * @return array{0: \App\Models\Lead|\App\Models\OperationLead|object|null, 1: string|null}
     */
    private function resolveCustomerModelForChats(Request $request): array
    {
        $user = $request->user();
        $customerInfo = Cache::get('customer_info_'.$user->id);

        $mobileRaw = (string) ($user->mobile ?? '');
        $mobile = preg_replace('/\D/', '', $mobileRaw);
        if (strlen($mobile) >= 10) {
            $mobile = substr($mobile, -10);
        }

        if ($customerInfo) {
            $customerId = $customerInfo['customer_id'] ?? null;
            $customerType = $customerInfo['customer_type'] ?? null;

            if ($customerType === 'consultation_booking') {
                $contactStored = ConsultationWebsiteBooking::normalizeContactToTenDigits(
                    (string) ($customerInfo['contact_no'] ?? $mobile)
                ) ?: $mobile;

                $booking = ConsultationWebsiteBooking::query()
                    ->whereTenDigitContact($contactStored)
                    ->orderByDesc('appointment_date')
                    ->orderByDesc('id')
                    ->first();

                if (! $booking && $mobile !== '') {
                    $booking = ConsultationWebsiteBooking::query()
                        ->whereTenDigitContact($mobile)
                        ->orderByDesc('appointment_date')
                        ->orderByDesc('id')
                        ->first();
                }

                $customer = new \stdClass;
                $customer->id = -1;
                $customer->customer_name = $booking->customer_name ?? ($customerInfo['customer_name'] ?? 'Customer');
                $customer->contact_no = $booking
                    ? (ConsultationWebsiteBooking::normalizeContactToTenDigits($booking->contact_no) ?: $contactStored)
                    : $contactStored;
                $customer->profile_image = null;

                return [$customer, 'consultation_booking'];
            }

            if ($customerType === 'operation_lead') {
                return [OperationLead::find($customerId), 'operation_lead'];
            }

            return [Lead::find($customerId), 'lead'];
        }

        if ($mobile === '') {
            return [null, null];
        }

        $lead = Lead::where('contact_no', $mobile)->first();
        $operationLead = OperationLead::where('contact_no', $mobile)->first();
        $crmCustomer = $operationLead ?? $lead;

        if ($crmCustomer) {
            return [$crmCustomer, $operationLead ? 'operation_lead' : 'lead'];
        }

        $bookingRecover = ConsultationWebsiteBooking::query()
            ->whereTenDigitContact($mobile)
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->first();

        if ($bookingRecover) {
            $customer = new \stdClass;
            $customer->id = -1;
            $customer->customer_name = $bookingRecover->customer_name ?? 'Customer';
            $customer->contact_no = ConsultationWebsiteBooking::normalizeContactToTenDigits(
                $bookingRecover->contact_no
            ) ?: $mobile;
            $customer->profile_image = null;

            return [$customer, 'consultation_booking'];
        }

        return [null, null];
    }

    /**
     * Operation lead IDs used in customer_chat_messages for this user (excludes Lead row ids and -1).
     *
     * @param  array<string, mixed>  $customerInfo
     * @return array<int>
     */
    private function operationLeadIdsForCustomerUser(Request $request, array $customerInfo, ?string $customerType = null): array
    {
        $user = $request->user();
        $contactNo = $customerInfo['contact_no'] ?? null;
        $mobile = preg_replace('/\D/', '', (string) ($user->mobile ?? ''));
        if (strlen($mobile) >= 10) {
            $mobile = substr($mobile, -10);
        }
        if (($contactNo === null || $contactNo === '') && $mobile !== '') {
            $contactNo = $mobile;
        }
        if ($contactNo) {
            $n = ConsultationWebsiteBooking::normalizeContactToTenDigits((string) $contactNo);
            $contactNo = $n ?: $contactNo;
        }

        $type = $customerType ?? ($customerInfo['customer_type'] ?? '');
        $cachedCustomerId = isset($customerInfo['customer_id']) ? (int) $customerInfo['customer_id'] : 0;

        $ids = [];
        if ($contactNo) {
            $ids = OperationLead::where('contact_no', $contactNo)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        }

        if ($type === 'operation_lead' && $cachedCustomerId > 0) {
            if (! in_array($cachedCustomerId, $ids, true)) {
                $ids[] = $cachedCustomerId;
            }
        }

        if ($contactNo) {
            $fromBookings = ConsultationWebsiteBooking::query()
                ->whereNotNull('operation_lead_id')
                ->whereTenDigitContact($contactNo)
                ->pluck('operation_lead_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray();
            $ids = array_merge($ids, $fromBookings);
        }

        $ids = array_values(array_unique(array_filter($ids, fn ($id) => (int) $id > 0)));

        return $ids;
    }

    /**
     * Preferred operation_lead id for outbound customer messages (must exist for sender_id).
     */
    private function primaryOperationLeadIdForMessaging(Request $request, array $customerInfo, ?string $customerType, array $operationLeadIds): ?int
    {
        if (! empty($operationLeadIds)) {
            return (int) $operationLeadIds[0];
        }
        $contactNo = $customerInfo['contact_no'] ?? null;
        if (! $contactNo) {
            $mobile = preg_replace('/\D/', '', (string) ($request->user()->mobile ?? ''));
            if (strlen($mobile) >= 10) {
                $mobile = substr($mobile, -10);
            }
            $contactNo = $mobile;
        }
        if ($contactNo) {
            $contactNo = ConsultationWebsiteBooking::normalizeContactToTenDigits((string) $contactNo) ?: $contactNo;
        }
        if (! $contactNo) {
            return null;
        }
        $b = ConsultationWebsiteBooking::query()
            ->whereTenDigitContact($contactNo)
            ->whereNotNull('operation_lead_id')
            ->orderByDesc('id')
            ->first();

        return $b ? (int) $b->operation_lead_id : null;
    }

    /** Chat message translation for CareApp / API clients (Gemini). */
    public function translate(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:8000',
            'target_language' => 'required|string|max:80',
        ]);

        $gemini = new GeminiService();
        $translated = $gemini->translate((string) $request->input('text'), (string) $request->input('target_language'));
        if ($translated === null) {
            return response()->json([
                'success' => false,
                'message' => filled(config('services.gemini.api_key'))
                    ? 'Translation service unavailable. Try GEMINI_MODEL in .env or check server logs.'
                    : 'GEMINI_API_KEY is not set in .env.',
            ], 503);
        }

        return response()->json(['success' => true, 'translated' => $translated]);
    }
}

