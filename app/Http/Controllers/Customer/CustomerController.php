<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\OperationDeploymentDetails;
use App\Models\ConsultationWebsiteBooking;
use App\Models\DeploymentLocationAttendance;
use App\Models\PaymentInvoice;
use App\Models\ReceivedPayment;
use App\Models\CustomerChatCall;
use App\Models\WhatsappMsgGroup;
use App\Facades\UserAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Services\GeminiService;
use App\Support\DoctorCustomerChatRealtime;

class CustomerController extends Controller
{
    public function dashboard()
    {
        // Get customer ID and type from session
        $customerId = Session::get('customer_id');
        $customerType = Session::get('customer_type', 'lead');
        $contactNo = Session::get('contact_no');
        
        if (!$customerId) {
            return redirect()->route('home')->with('error', 'Please login to access customer dashboard.');
        }

        // Get customer data based on type
        $customer = null;
        if ($customerType === 'consultation_booking') {
            $contactNo = ConsultationWebsiteBooking::normalizeContactToTenDigits((string) $contactNo)
                ?: $contactNo;
            $customer = new \stdClass();
            $customer->id = null;
            $customer->customer_name = Session::get('customer_name', 'Customer');
            $customer->contact_no = $contactNo;
            $customer->profile_image = null;
        } elseif ($customerType === 'operation_lead') {
            $customer = OperationLead::find($customerId);
        } else {
            $customer = Lead::find($customerId);
        }
        
        // If customer not found by ID, try to find by contact_no (in case of data inconsistency)
        if ($customerType !== 'consultation_booking' && !$customer && $contactNo) {
            // Try to find in OperationLead first
            $customer = OperationLead::where('contact_no', $contactNo)->first();
            if ($customer) {
                // Update session with found customer
                Session::put('customer_id', $customer->id);
                Session::put('customer_type', 'operation_lead');
                $customerId = $customer->id;
                $customerType = 'operation_lead';
            } else {
                // Try to find in Lead
                $customer = Lead::where('contact_no', $contactNo)->first();
                if ($customer) {
                    // Update session with found customer
                    Session::put('customer_id', $customer->id);
                    Session::put('customer_type', 'lead');
                    $customerId = $customer->id;
                    $customerType = 'lead';
                }
            }
        }
        
        // If still not found, clear session and redirect (consultation-only users already have synthetic $customer)
        if (!$customer) {
            Session::forget(['customer_id', 'customer_name', 'customer_type', 'login_type', 'contact_no']);
            return redirect()->route('home')->with('error', 'Customer account not found.');
        }
        
        // Ensure contact_no is in session for future lookups
        if (!$contactNo && $customer->contact_no) {
            Session::put('contact_no', $customer->contact_no);
        }

        // Get all leads/operation leads with same contact number
        $contactNo = $customer->contact_no;
        
        $allLeads = Lead::where('contact_no', $contactNo)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $allOperationLeads = OperationLead::where('contact_no', $contactNo)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get payment invoices and received payments (for operation leads)
        $paymentInvoices = collect();
        $receivedPayments = collect();
        
        if ($customerType === 'operation_lead') {
            $paymentInvoices = PaymentInvoice::where('operation_lead_id', $customerId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            $receivedPayments = ReceivedPayment::whereIn('payment_invoice_id', $paymentInvoices->pluck('id'))
                ->orderBy('received_date', 'desc')
                ->get();
        }

        // Get all services
        $services = \App\Models\Service::orderBy('name', 'asc')->get();

        // Get all locations
        $locations = \App\Models\Location::orderBy('name', 'asc')->get();

        // Get active queries (leads and operation leads with active status)
        // Active statuses: 'Active', 'In Progress', 'Ongoing', 'Active Deployment'
        $activeStatuses = ['Active', 'In Progress', 'Ongoing', 'Active Deployment'];
        
        $activeLeads = Lead::where('contact_no', $contactNo)
            ->whereIn('status', $activeStatuses)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $activeOperationLeads = OperationLead::where('contact_no', $contactNo)
            ->whereIn('status', $activeStatuses)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get active leads with assigned vendors/freelancers for card display
        $activeLeadsWithAssignments = collect();
        
        // Get all OperationLead IDs for this customer (by contact_no)
        $operationLeadIds = OperationLead::where('contact_no', $contactNo)->pluck('id')->toArray();
        
        // Also append session customer id when it refers to CRM (never -1 pseudo id for consultation-only login)
        if ($customerType !== 'consultation_booking' && !in_array((int) $customerId, $operationLeadIds, true)) {
            $operationLeadIds[] = $customerId;
        }
        
        // Get deployments for all operation leads with this contact number
        // Get all deployments with vendors/freelancers assigned (don't filter by status too strictly)
        $deployments = \App\Models\OperationDeploymentDetails::whereIn('operation_lead_id', $operationLeadIds)
            ->where(function($query) {
                $query->whereNotNull('vendor_id')
                      ->orWhereNotNull('freelance_staff_id');
            })
            ->with(['vendor', 'freelanceStaff', 'operationLead'])
            ->get();
        
        \Log::info('Customer Dashboard - Active Leads', [
            'customer_id' => $customerId,
            'contact_no' => $contactNo,
            'operation_lead_ids' => $operationLeadIds,
            'deployments_count' => $deployments->count(),
            'deployments_with_vendor' => $deployments->whereNotNull('vendor_id')->count(),
            'deployments_with_freelancer' => $deployments->whereNotNull('freelance_staff_id')->count()
        ]);
        
        foreach ($deployments as $deployment) {
            $lead = $deployment->operationLead;
            if (!$lead) {
                \Log::warning('Customer Dashboard - Deployment without OperationLead', [
                    'deployment_id' => $deployment->id,
                    'operation_lead_id' => $deployment->operation_lead_id
                ]);
                continue;
            }
            
            // Get vendor if assigned
            if ($deployment->vendor_id) {
                // Try to get vendor from relationship first, then direct query
                $vendor = $deployment->vendor;
                if (!$vendor) {
                    $vendor = \App\Models\Vendor::find($deployment->vendor_id);
                }
                
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
                        'customer_location_attendance_enabled' => (bool) ($lead->customer_location_attendance_enabled ?? false),
                    ]);
                } else {
                    \Log::warning('Customer Dashboard - Vendor not found', [
                        'deployment_id' => $deployment->id,
                        'vendor_id' => $deployment->vendor_id
                    ]);
                }
            }
            
            // Get freelancer if assigned
            if ($deployment->freelance_staff_id) {
                // Try to get freelancer from relationship first, then direct query
                $freelancer = $deployment->freelanceStaff;
                if (!$freelancer) {
                    $freelancer = \App\Models\JobRequest::find($deployment->freelance_staff_id);
                }
                
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
                        'customer_location_attendance_enabled' => (bool) ($lead->customer_location_attendance_enabled ?? false),
                    ]);
                } else {
                    \Log::warning('Customer Dashboard - Freelancer not found', [
                        'deployment_id' => $deployment->id,
                        'freelance_staff_id' => $deployment->freelance_staff_id
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
                return $row->operation_lead_id . '|' . $type . '|' . $id;
            });
        $activeLeadsWithAssignments = $activeLeadsWithAssignments->map(function ($assignment) use ($assignmentAttendanceMap) {
            $key = $assignment['lead_id'] . '|' . $assignment['assignment_type'] . '|' . $assignment['assignment_id'];
            $attendance = $assignmentAttendanceMap->get($key);
            $assignment['today_attendance'] = $attendance;
            return $assignment;
        });
        
        \Log::info('Customer Dashboard - Active Leads With Assignments', [
            'total_assignments' => $activeLeadsWithAssignments->count(),
            'assignments' => $activeLeadsWithAssignments->toArray()
        ]);

        $customerName = $customer->customer_name ?? 'N/A';
        $page_heading = 'Customer Dashboard';

        $consultationWebsiteBookings = ConsultationWebsiteBooking::query()
            ->whereTenDigitContact($contactNo)
            ->with(['doctorRequest', 'consultationService'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->get();

        return view('customer.dashboard', compact(
            'page_heading',
            'customer',
            'customerType',
            'customerName',
            'allLeads',
            'allOperationLeads',
            'paymentInvoices',
            'receivedPayments',
            'services',
            'locations',
            'activeLeads',
            'activeOperationLeads',
            'activeLeadsWithAssignments',
            'consultationWebsiteBookings'
        ));
    }

    public function joinConsultationMeeting(int $bookingId)
    {
        $ctx = $this->webSessionCustomerContext();
        if (! $ctx) {
            return redirect()->route('home')->with('error', 'Please login first.');
        }
        [$customerModel, $customerType, $sessionCustomerId] = $ctx;
        $contactNo = Session::get('contact_no') ?? ($customerModel->contact_no ?? null);
        $contactNo = ConsultationWebsiteBooking::normalizeContactToTenDigits((string) $contactNo) ?: $contactNo;

        $booking = ConsultationWebsiteBooking::query()
            ->whereKey($bookingId)
            ->whereTenDigitContact((string) $contactNo)
            ->first();

        if (! $booking) {
            return back()->with('error', 'Meeting booking not found for your account.');
        }
        if (($booking->consultation_mode ?? '') !== 'online' || empty($booking->online_meeting_link)) {
            return back()->with('error', 'Online meeting link is not available for this booking yet.');
        }

        $join = ConsultationWebsiteBooking::meetingJoinStatus($booking);
        if (! $join['allowed']) {
            return back()->with('error', $join['reason'] ?: 'Meeting is not open right now.');
        }

        return redirect()->away($booking->online_meeting_link);
    }

    public function personalDetails()
    {
        $customerId = Session::get('customer_id');
        $customerType = Session::get('customer_type', 'lead');
        
        if (!$customerId) {
            return redirect()->route('home')->with('error', 'Please login to access customer dashboard.');
        }

        // Get customer data based on type
        if ($customerType === 'operation_lead') {
            $customer = OperationLead::find($customerId);
        } else {
            $customer = Lead::find($customerId);
        }
        
        if (!$customer) {
            Session::forget(['customer_id', 'customer_name', 'customer_type', 'login_type']);
            return redirect()->route('home')->with('error', 'Customer account not found.');
        }

        $page_heading = 'Personal Details';
        $customerName = $customer->customer_name ?? 'N/A';

        return view('customer.personal-details', compact('page_heading', 'customer', 'customerType', 'customerName'));
    }

    public function chats()
    {
        $customerId = Session::get('customer_id');
        $customerType = Session::get('customer_type', 'lead');
        
        if (!$customerId) {
            return redirect()->route('home')->with('error', 'Please login to access customer dashboard.');
        }

        // Get customer data - check both Lead and OperationLead
        $customer = null;
        $isOperationLead = false;
        
        // First check OperationLead
        $operationLead = OperationLead::find($customerId);
        if ($operationLead) {
            $customer = $operationLead;
            $isOperationLead = true;
            $customerType = 'operation_lead';
        } else {
            // Check Lead
            $lead = Lead::find($customerId);
            if ($lead) {
                $customer = $lead;
                // Check if this lead has deployments (meaning it's actually an operation lead)
                $hasDeployments = \App\Models\OperationDeploymentDetails::where('operation_lead_id', $customerId)->exists();
                if ($hasDeployments) {
                    $isOperationLead = true;
                    $customerType = 'operation_lead';
                    // Update session to reflect correct type
                    Session::put('customer_type', 'operation_lead');
                }
            }
        }
        
        if (!$customer) {
            Session::forget(['customer_id', 'customer_name', 'customer_type', 'login_type']);
            return redirect()->route('home')->with('error', 'Customer account not found.');
        }

        // Get vendors and freelancers assigned to this customer
        // Check deployments by operation_lead_id (which should match customerId)
        // Also check if customer is a Lead that has been converted to OperationLead
        $deployments = \App\Models\OperationDeploymentDetails::where('operation_lead_id', $customerId)
            ->with(['vendor', 'freelanceStaff'])
            ->get();
        
        // If no deployments found with direct customerId, check if customer is a Lead
        // and find deployments via OperationLead that has the same contact_no
        if ($deployments->count() === 0 && $customer && isset($customer->contact_no)) {
            $operationLeads = OperationLead::where('contact_no', $customer->contact_no)->pluck('id');
            if ($operationLeads->count() > 0) {
                $deployments = \App\Models\OperationDeploymentDetails::whereIn('operation_lead_id', $operationLeads->toArray())
                    ->with(['vendor', 'freelanceStaff'])
                    ->get();
            }
        }
        
        // Debug: Log deployments to check if vendors are being loaded
        \Log::info('Customer Chats - Deployments', [
            'customer_id' => $customerId,
            'deployments_count' => $deployments->count(),
            'vendors_in_deployments' => $deployments->whereNotNull('vendor_id')->count(),
            'freelancers_in_deployments' => $deployments->whereNotNull('freelance_staff_id')->count(),
            'deployment_vendor_ids' => $deployments->whereNotNull('vendor_id')->pluck('vendor_id')->toArray(),
            'deployment_freelancer_ids' => $deployments->whereNotNull('freelance_staff_id')->pluck('freelance_staff_id')->toArray()
        ]);
        
        // Debug: Check if vendor relationships are loaded
        foreach ($deployments->whereNotNull('vendor_id') as $deployment) {
            \Log::info('Customer Chats - Deployment Vendor Check', [
                'deployment_id' => $deployment->id,
                'vendor_id' => $deployment->vendor_id,
                'vendor_loaded' => $deployment->relationLoaded('vendor'),
                'vendor_exists' => $deployment->vendor ? true : false,
                'vendor_name' => $deployment->vendor ? ($deployment->vendor->name ?? $deployment->vendor->customer_name ?? 'N/A') : 'NULL'
            ]);
        }

        // Get vendors - ensure we filter out nulls properly
        // First, get all unique vendor IDs from deployments
        $vendorIds = $deployments->whereNotNull('vendor_id')->pluck('vendor_id')->unique()->filter()->values();
        
        // Directly fetch vendors by IDs to ensure they're loaded
        $vendors = collect();
        if ($vendorIds->count() > 0) {
            $vendors = \App\Models\Vendor::whereIn('id', $vendorIds->toArray())->get();
        }
        
        \Log::info('Customer Chats - Direct Vendor Query', [
            'vendor_ids_from_deployments' => $vendorIds->toArray(),
            'vendors_found' => $vendors->count(),
            'vendor_names' => $vendors->pluck('name')->toArray()
        ]);

        // Get freelancers - ensure we filter out nulls properly
        // First, get all unique freelancer IDs from deployments
        $freelancerIds = $deployments->whereNotNull('freelance_staff_id')->pluck('freelance_staff_id')->unique()->filter()->values();
        
        // Directly fetch freelancers by IDs to ensure they're loaded
        $freelancers = collect();
        if ($freelancerIds->count() > 0) {
            $freelancers = \App\Models\JobRequest::whereIn('id', $freelancerIds->toArray())->get();
        }

        // Debug: Log raw vendors and freelancers
        \Log::info('Customer Chats - Raw Data', [
            'vendors_count' => $vendors->count(),
            'freelancers_count' => $freelancers->count(),
            'vendor_ids' => $vendors->pluck('id')->toArray(),
            'vendor_names' => $vendors->pluck('name')->toArray(),
            'freelancer_ids' => $freelancers->pluck('id')->toArray()
        ]);

        // Enrich vendors with unread count and last message
        $enrichedVendors = $vendors->map(function($vendor) use ($customerId) {
            if (!$vendor || !$vendor->id) {
                \Log::warning('Customer Chats - Null vendor found', ['vendor' => $vendor]);
                return null;
            }
            
            // Ensure vendor has name property - check both name and customer_name
            if (empty($vendor->name)) {
                if (!empty($vendor->customer_name)) {
                    $vendor->name = $vendor->customer_name;
                } else {
                    $vendor->name = 'Vendor #' . $vendor->id; // Fallback name
                }
            }
            
            $unread = \App\Models\CustomerChatMessage::where('sender_type', 'vendor')
                ->where('sender_id', $vendor->id)
                ->where('receiver_type', 'customer')
                ->where('receiver_id', $customerId)
                ->where('is_read', false)
                ->count();

            $lastMsg = \App\Models\CustomerChatMessage::where(function($q) use ($customerId, $vendor) {
                $q->where('sender_type', 'customer')
                  ->where('sender_id', $customerId)
                  ->where('receiver_type', 'vendor')
                  ->where('receiver_id', $vendor->id);
            })->orWhere(function($q) use ($customerId, $vendor) {
                $q->where('sender_type', 'vendor')
                  ->where('sender_id', $vendor->id)
                  ->where('receiver_type', 'customer')
                  ->where('receiver_id', $customerId);
            })->orderByDesc('created_at')->first();
            
            $vendor->unread_count = $unread;
            $vendor->last_message = $lastMsg;
            $vendor->chat_type = 'vendor';
            
            \Log::info('Customer Chats - Enriched Vendor', [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'chat_type' => $vendor->chat_type,
                'unread_count' => $vendor->unread_count
            ]);
            
            return $vendor;
        })->filter(); // Remove any nulls

        // Enrich freelancers with unread count and last message
        $enrichedFreelancers = $freelancers->map(function($freelancer) use ($customerId) {
            $unread = \App\Models\CustomerChatMessage::where('sender_type', 'freelancer')
                ->where('sender_id', $freelancer->id)
                ->where('receiver_type', 'customer')
                ->where('receiver_id', $customerId)
                ->where('is_read', false)
                ->count();

            $lastMsg = \App\Models\CustomerChatMessage::where(function($q) use ($customerId, $freelancer) {
                $q->where('sender_type', 'customer')
                  ->where('sender_id', $customerId)
                  ->where('receiver_type', 'freelancer')
                  ->where('receiver_id', $freelancer->id);
            })->orWhere(function($q) use ($customerId, $freelancer) {
                $q->where('sender_type', 'freelancer')
                  ->where('sender_id', $freelancer->id)
                  ->where('receiver_type', 'customer')
                  ->where('receiver_id', $customerId);
            })->orderByDesc('created_at')->first();

            $freelancer->unread_count = $unread;
            $freelancer->last_message = $lastMsg;
            $freelancer->chat_type = 'freelancer';
            return $freelancer;
        });

        $operationLeadIdsForChats = $this->webOperationLeadIdsForChats($customer, (int) $customerId, $customerType);
        $contactForBooking = Session::get('contact_no') ?? ($customer->contact_no ?? null);
        if ($contactForBooking) {
            $contactForBooking = ConsultationWebsiteBooking::normalizeContactToTenDigits((string) $contactForBooking) ?: $contactForBooking;
        }

        $enrichedDoctors = collect();
        if (! empty($operationLeadIdsForChats) || ! empty($contactForBooking)) {
            $doctorBookings = ConsultationWebsiteBooking::query()
                ->whereNotNull('doctor_request_id')
                ->where(function ($sub) use ($operationLeadIdsForChats, $contactForBooking) {
                    $first = true;
                    if (! empty($operationLeadIdsForChats)) {
                        $sub->whereIn('operation_lead_id', $operationLeadIdsForChats);
                        $first = false;
                    }
                    if (! empty($contactForBooking)) {
                        if ($first) {
                            $sub->where(fn ($s) => $s->whereTenDigitContact($contactForBooking));
                        } else {
                            $sub->orWhere(fn ($s) => $s->whereTenDigitContact($contactForBooking));
                        }
                    }
                })
                ->with('doctorRequest')
                ->orderByDesc('created_at')
                ->get();

            $idsForMessages = $operationLeadIdsForChats;
            if (empty($idsForMessages)) {
                $pid = $this->webPrimaryOperationLeadIdForMessaging($customer, (int) $customerId, $customerType, $operationLeadIdsForChats);
                if ($pid) {
                    $idsForMessages = [$pid];
                }
            }

            $doctorRealtimeLeadIdsExpanded = array_values(array_unique(array_map(
                static fn ($id) => (int) $id,
                $idsForMessages
            )));
            foreach ($doctorRealtimeLeadIdsExpanded as $expId) {
                $olExpand = OperationLead::find($expId);
                if ($olExpand && $olExpand->contact_no) {
                    $doctorRealtimeLeadIdsExpanded = array_merge(
                        $doctorRealtimeLeadIdsExpanded,
                        OperationLead::where('contact_no', $olExpand->contact_no)->pluck('id')->map(
                            fn ($id) => (int) $id
                        )->all()
                    );
                }
            }
            $doctorRealtimeLeadIdsExpanded = array_values(array_unique(array_filter(
                $doctorRealtimeLeadIdsExpanded,
                static fn ($id) => $id > 0
            )));

            $seenDoctorIds = [];
            foreach ($doctorBookings as $booking) {
                $dr = $booking->doctorRequest;
                if (! $dr || ! $booking->doctor_request_id) {
                    continue;
                }
                if (isset($seenDoctorIds[$booking->doctor_request_id])) {
                    continue;
                }
                $seenDoctorIds[$booking->doctor_request_id] = true;

                $unread = \App\Models\CustomerChatMessage::where('sender_type', 'doctor')
                    ->where('sender_id', $dr->id)
                    ->where('receiver_type', 'customer')
                    ->whereIn('receiver_id', $idsForMessages)
                    ->where('is_read', false)
                    ->count();

                $lastMsg = \App\Models\CustomerChatMessage::where(function ($q) use ($idsForMessages, $dr) {
                    $q->where('sender_type', 'customer')
                        ->whereIn('sender_id', $idsForMessages)
                        ->where('receiver_type', 'doctor')
                        ->where('receiver_id', $dr->id);
                })->orWhere(function ($q) use ($idsForMessages, $dr) {
                    $q->where('sender_type', 'doctor')
                        ->where('sender_id', $dr->id)
                        ->where('receiver_type', 'customer')
                        ->whereIn('receiver_id', $idsForMessages);
                })->orderByDesc('created_at')->first();

                if (! $dr->name) {
                    $dr->name = 'Doctor #'.$dr->id;
                }
                $dr->unread_count = $unread;
                $dr->last_message = $lastMsg;
                $dr->chat_type = 'doctor';
                $sigList = [];
                foreach ($doctorRealtimeLeadIdsExpanded as $olid) {
                    $sigList[] = DoctorCustomerChatRealtime::threadSignature((int) $dr->id, $olid);
                }
                $dr->customer_thread_signatures_json = json_encode(array_values(array_unique($sigList)));
                $enrichedDoctors->push($dr);
            }
        }

        // Debug: Log enriched data before combining
        \Log::info('Customer Chats - Before Combining', [
            'enriched_vendors_count' => $enrichedVendors->count(),
            'enriched_freelancers_count' => $enrichedFreelancers->count(),
            'enriched_doctors_count' => $enrichedDoctors->count(),
            'vendor_ids' => $enrichedVendors->pluck('id')->toArray(),
            'vendor_names' => $enrichedVendors->pluck('name')->toArray(),
            'freelancer_ids' => $enrichedFreelancers->pluck('id')->toArray()
        ]);
        
        // Combine and sort
        $allChats = $enrichedVendors->concat($enrichedFreelancers)->concat($enrichedDoctors);
        $sortedChats = $allChats->sort(function($a, $b) {
            if ($a->unread_count > 0 && $b->unread_count == 0) return -1;
            if ($a->unread_count == 0 && $b->unread_count > 0) return 1;
            $aTime = $a->last_message ? strtotime($a->last_message->created_at) : 0;
            $bTime = $b->last_message ? strtotime($b->last_message->created_at) : 0;
            return $bTime <=> $aTime;
        })->values();
        
        // Debug: Log final sorted chats
        \Log::info('Customer Chats - Final Sorted Chats', [
            'total_chats' => $sortedChats->count(),
            'chat_types' => $sortedChats->pluck('chat_type')->toArray(),
            'chat_names' => $sortedChats->pluck('name')->toArray(),
            'chat_ids' => $sortedChats->pluck('id')->toArray(),
            'customer_type' => $customerType,
            'has_deployments' => $deployments->count() > 0
        ]);

        // If customer has deployments but is not marked as operation_lead, update customerType
        if ($sortedChats->count() > 0 && $customerType !== 'operation_lead') {
            $customerType = 'operation_lead';
            Session::put('customer_type', 'operation_lead');
        }

        $page_heading = 'Chats';
        $customerName = $customer->customer_name ?? 'N/A';

        $customerDoctorRealtimeSignatures = [];
        foreach ($sortedChats as $chat) {
            if (($chat->chat_type ?? '') !== 'doctor') {
                continue;
            }
            foreach (json_decode($chat->customer_thread_signatures_json ?? '[]', true) ?: [] as $s) {
                if ($s !== null && $s !== '') {
                    $customerDoctorRealtimeSignatures[$s] = true;
                }
            }
        }

        $crmLeadIdsForRealtime = array_values(array_unique(array_filter(
            array_map(static fn ($id) => (int) $id, $operationLeadIdsForChats),
            static fn ($id) => $id > 0
        )));
        if ($crmLeadIdsForRealtime === []) {
            $fallbackLead = $this->webPrimaryOperationLeadIdForMessaging(
                $customer,
                (int) $customerId,
                $customerType,
                $operationLeadIdsForChats
            );
            if ($fallbackLead) {
                $crmLeadIdsForRealtime = [(int) $fallbackLead];
            }
        }
        foreach ($crmLeadIdsForRealtime as $lid) {
            $olRow = OperationLead::find($lid);
            if ($olRow && $olRow->contact_no) {
                $crmLeadIdsForRealtime = array_merge(
                    $crmLeadIdsForRealtime,
                    OperationLead::where('contact_no', $olRow->contact_no)->pluck('id')->map(
                        fn ($id) => (int) $id
                    )->all()
                );
            }
        }
        $crmLeadIdsForRealtime = array_values(array_unique(array_filter(
            $crmLeadIdsForRealtime,
            static fn ($id) => $id > 0
        )));

        return view('customer.chats', [
            'page_heading' => $page_heading,
            'customer' => $customer,
            'customerType' => $customerType,
            'customerName' => $customerName,
            'sortedChats' => $sortedChats,
            'customerDoctorRealtimeSignatures' => array_keys($customerDoctorRealtimeSignatures),
            'customerRealtimeOperationLeadIds' => $crmLeadIdsForRealtime,
        ]);
    }

    public function getMessages($chatType, $chatId, Request $request)
    {
        $ctx = $this->webSessionCustomerContext();
        if (! $ctx) {
            \Log::error('Customer getMessages: No customer_id or contact_no in session');
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        [$customerModel, $customerType, $sessionCustomerId] = $ctx;
        $contactNo = Session::get('contact_no') ?? ($customerModel->contact_no ?? null);

        \Log::info('Customer getMessages: Request received', [
            'customer_id' => $sessionCustomerId,
            'contact_no' => $contactNo,
            'chat_type' => $chatType,
            'chat_id' => $chatId,
        ]);

        $operationLeadIds = $this->webOperationLeadIdsForChats($customerModel, $sessionCustomerId, $customerType);
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
                    'user_message' => 'No customer profile is linked to this chat yet.',
                    'window_start' => null,
                    'window_end' => null,
                    'next_window_start' => null,
                ],
            ]);
        }

        $isAssigned = false;
        if ($chatType === 'vendor') {
            $isAssigned = \App\Models\OperationDeploymentDetails::whereIn('operation_lead_id', $operationLeadIds)
                ->where('vendor_id', $chatId)
                ->exists();
        } elseif ($chatType === 'freelancer') {
            $isAssigned = \App\Models\OperationDeploymentDetails::whereIn('operation_lead_id', $operationLeadIds)
                ->where('freelance_staff_id', $chatId)
                ->exists();
        } elseif ($chatType === 'doctor') {
            $primaryId = $this->webPrimaryOperationLeadIdForMessaging($customerModel, $sessionCustomerId, $customerType, $operationLeadIds);
            $isAssigned = $primaryId
                && ConsultationWebsiteBooking::doctorHasBookedAnyLead((int) $chatId, (int) $primaryId);
        } else {
            return response()->json(['error' => 'Invalid chat type'], 400);
        }

        $hasExistingMessages = false;
        if (! empty($operationLeadIds)) {
            $hasExistingMessages = \App\Models\CustomerChatMessage::where(function ($q) use ($operationLeadIds, $chatType, $chatId) {
                $q->where('sender_type', 'customer')
                    ->whereIn('sender_id', $operationLeadIds)
                    ->where('receiver_type', $chatType)
                    ->where('receiver_id', $chatId);
            })->orWhere(function ($q) use ($operationLeadIds, $chatType, $chatId) {
                $q->where('sender_type', $chatType)
                    ->where('sender_id', $chatId)
                    ->where('receiver_type', 'customer')
                    ->whereIn('receiver_id', $operationLeadIds);
            })->exists();
        }

        if (! $isAssigned && ! $hasExistingMessages) {
            \Log::warning('Customer getMessages: Not assigned and no existing messages', [
                'customer_id' => $sessionCustomerId,
                'contact_no' => $contactNo,
                'operation_lead_ids' => $operationLeadIds,
                'chat_type' => $chatType,
                'chat_id' => $chatId,
                'is_assigned' => $isAssigned,
                'has_existing_messages' => $hasExistingMessages,
            ]);

            return response()->json(['error' => 'Not assigned to you'], 403);
        }

        // Query messages for all customer IDs with this contact number
        $query = \App\Models\CustomerChatMessage::where(function($q) use ($operationLeadIds, $chatType, $chatId) {
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

        // Get call history for this conversation (using all customer IDs)
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

        // Combine messages and calls, sort by created_at
        $combined = collect();
        
        // Add messages
        foreach ($messages as $msg) {
            $combined->push([
                'type' => 'message',
                'id' => $msg->id,
                'created_at' => $msg->created_at,
                'data' => $msg
            ]);
        }
        
        // Add calls
        foreach ($calls as $call) {
            $combined->push([
                'type' => 'call',
                'id' => 'call_' . $call->id,
                'created_at' => $call->call_started_at ?? $call->created_at,
                'data' => $call
            ]);
        }
        
        // Sort by created_at
        $sorted = $combined->sortBy('created_at')->values();
        
        // If last_message_id is provided, filter out items before it
        if ($request->has('last_message_id') && $request->last_message_id) {
            $lastMsg = $messages->where('id', $request->last_message_id)->first();
            if ($lastMsg) {
                $sorted = $sorted->filter(function($item) use ($lastMsg) {
                    if ($item['type'] === 'message') {
                        return $item['data']->id > $lastMsg->id;
                    } else {
                        return $item['created_at'] > $lastMsg->created_at;
                    }
                })->values();
            }
        }

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
            $ctx = $this->webSessionCustomerContext();
            if (! $ctx) {
                \Log::error('Customer sendMessage: No session customer');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            [$customerModel, $customerType, $sessionCustomerId] = $ctx;

            \Log::info('Customer sendMessage: Request received', [
                'customer_id' => $sessionCustomerId,
                'receiver_type' => $request->receiver_type,
                'receiver_id' => $request->receiver_id,
                'has_message' => ! empty($request->message),
                'has_attachment' => $request->hasFile('attachment'),
            ]);

            $hasMessage = ! empty(trim($request->message ?? ''));
            $hasAttachment = $request->hasFile('attachment');

            if (! $hasMessage && ! $hasAttachment) {
                return response()->json(['error' => 'Message or attachment is required'], 422);
            }

            $request->validate([
                'receiver_type' => 'required|in:vendor,freelancer,doctor',
                'receiver_id' => 'required|integer',
                'message' => 'nullable|string',
                'attachment' => 'nullable|file|max:10240',
                'reply_to_id' => 'nullable|exists:customer_chat_messages,id',
            ]);

            $contactNo = Session::get('contact_no') ?? ($customerModel->contact_no ?? null);
            $operationLeadIds = $this->webOperationLeadIdsForChats($customerModel, $sessionCustomerId, $customerType);
            $receiverId = (int) $request->receiver_id;

            $isAssigned = false;
            if ($request->receiver_type === 'vendor') {
                $isAssigned = \App\Models\OperationDeploymentDetails::whereIn('operation_lead_id', $operationLeadIds)
                    ->where('vendor_id', $receiverId)
                    ->exists();
            } elseif ($request->receiver_type === 'freelancer') {
                $isAssigned = \App\Models\OperationDeploymentDetails::whereIn('operation_lead_id', $operationLeadIds)
                    ->where('freelance_staff_id', $receiverId)
                    ->exists();
            } elseif ($request->receiver_type === 'doctor') {
                $primaryForBooking = $this->webPrimaryOperationLeadIdForMessaging($customerModel, $sessionCustomerId, $customerType, $operationLeadIds);
                $isAssigned = $primaryForBooking
                    && ConsultationWebsiteBooking::doctorHasBookedAnyLead($receiverId, (int) $primaryForBooking);
            } else {
                return response()->json(['error' => 'Invalid receiver type'], 400);
            }

            $hasExistingMessages = false;
            if (! empty($operationLeadIds)) {
                $hasExistingMessages = \App\Models\CustomerChatMessage::where(function ($q) use ($operationLeadIds, $request) {
                    $q->where('sender_type', 'customer')
                        ->whereIn('sender_id', $operationLeadIds)
                        ->where('receiver_type', $request->receiver_type)
                        ->where('receiver_id', $request->receiver_id);
                })->orWhere(function ($q) use ($operationLeadIds, $request) {
                    $q->where('sender_type', $request->receiver_type)
                        ->where('sender_id', $request->receiver_id)
                        ->where('receiver_type', 'customer')
                        ->whereIn('receiver_id', $operationLeadIds);
                })->exists();
            }

            if (! $isAssigned && ! $hasExistingMessages) {
                \Log::warning('Customer sendMessage: Not assigned and no existing messages', [
                    'customer_id' => $sessionCustomerId,
                    'contact_no' => $contactNo,
                    'operation_lead_ids' => $operationLeadIds,
                    'receiver_type' => $request->receiver_type,
                    'receiver_id' => $request->receiver_id,
                    'is_assigned' => $isAssigned,
                    'has_existing_messages' => $hasExistingMessages,
                ]);

                return response()->json(['error' => 'Not assigned to you'], 403);
            }

            $messageCustomerId = $this->webPrimaryOperationLeadIdForMessaging($customerModel, $sessionCustomerId, $customerType, $operationLeadIds);
            if (! $messageCustomerId) {
                return response()->json(['error' => 'Unable to determine customer ID'], 400);
            }

            if ($request->receiver_type === 'doctor') {
                $scheduleGate = ConsultationWebsiteBooking::doctorCustomerChatScheduleStatus(
                    $receiverId,
                    (int) $messageCustomerId
                );
                if (! $scheduleGate['allowed']) {
                    return response()->json([
                        'error' => $scheduleGate['user_message'] ?? 'You can chat with the doctor only during your booked appointment time.',
                        'chat_schedule' => $scheduleGate,
                    ], 403);
                }
            }

            $data = [
                'sender_type' => 'customer',
                'sender_id' => $messageCustomerId,
                'receiver_type' => $request->receiver_type,
                'receiver_id' => $request->receiver_id,
                'message' => $hasMessage ? trim($request->message) : null,
                'is_read' => false,
                'reply_to_id' => $request->reply_to_id ?? null
            ];

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('customer_chat_attachments', 'public');
                $data['attachment'] = $path;
                $data['attachment_type'] = $file->getMimeType();
            }

            \Log::info('Customer sendMessage: Creating message', $data);

            $message = \App\Models\CustomerChatMessage::create($data);

            \Log::info('Customer sendMessage: Message created successfully', [
                'message_id' => $message->id,
                'sender_type' => $message->sender_type,
                'sender_id' => $message->sender_id,
                'receiver_type' => $message->receiver_type,
                'receiver_id' => $message->receiver_id
            ]);

            return response()->json($message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Customer sendMessage: Validation error', [
                'errors' => $e->errors()
            ]);
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Customer sendMessage: Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }
    }

    public function markAsRead($chatType, $chatId)
    {
        $ctx = $this->webSessionCustomerContext();
        if (! $ctx) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        [$customerModel, $customerType, $sessionCustomerId] = $ctx;

        $receiverIds = $this->webOperationLeadIdsForChats($customerModel, $sessionCustomerId, $customerType);

        \App\Models\CustomerChatMessage::where('sender_type', $chatType)
            ->where('sender_id', $chatId)
            ->where('receiver_type', 'customer')
            ->whereIn('receiver_id', $receiverIds)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($chatType === 'doctor') {
            $primaryLead = $this->webPrimaryOperationLeadIdForMessaging(
                $customerModel,
                $sessionCustomerId,
                $customerType,
                $receiverIds
            );
            if ($primaryLead) {
                DoctorCustomerChatRealtime::broadcastUnreadRefresh((int) $chatId, $primaryLead, 'mark_read');
            }
        }

        return response()->json(['success' => true]);
    }

    public function callOffer(Request $request)
    {
        $customerId = Session::get('customer_id');
        if (!$customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $receiverType = $request->receiver_type;
        $receiverId = $request->receiver_id;
        $offer = $request->offer;

        // Log call initiation
        $callLog = CustomerChatCall::create([
            'caller_type' => 'customer',
            'caller_id' => $customerId,
            'receiver_type' => $receiverType,
            'receiver_id' => $receiverId,
            'call_status' => 'initiated',
            'call_started_at' => now(),
        ]);

        // Store call ID in cache for later update
        $callIdCacheKey = "call_id_customer_{$customerId}_{$receiverType}_{$receiverId}";
        \Illuminate\Support\Facades\Cache::put($callIdCacheKey, $callLog->id, 300);

        // Store offer in cache for receiver to pick up (format: call_offer_{receiverType}_{receiverId}_{customerId})
        $cacheKey = "call_offer_{$receiverType}_{$receiverId}_{$customerId}";
        \Illuminate\Support\Facades\Cache::put($cacheKey, $offer, 60); // 60 seconds expiry

        return response()->json(['success' => true, 'call_id' => $callLog->id]);
    }

    public function callAnswer(Request $request)
    {
        $customerId = Session::get('customer_id');
        if (!$customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $receiverType = $request->receiver_type;
        $receiverId = $request->receiver_id;

        // Update call status to connected if call was initiated by customer
        $callIdCacheKey = "call_id_customer_{$customerId}_{$receiverType}_{$receiverId}";
        $callId = \Illuminate\Support\Facades\Cache::get($callIdCacheKey);
        if ($callId) {
            CustomerChatCall::where('id', $callId)
                ->where('caller_type', 'customer')
                ->where('caller_id', $customerId)
                ->where('receiver_type', $receiverType)
                ->where('receiver_id', $receiverId)
                ->update(['call_status' => 'connected']);
        }

        // Also check for incoming call from vendor/freelancer
        $incomingCallIdCacheKey = "call_id_{$receiverType}_{$receiverId}_customer_{$customerId}";
        $incomingCallId = \Illuminate\Support\Facades\Cache::get($incomingCallIdCacheKey);
        if ($incomingCallId) {
            CustomerChatCall::where('id', $incomingCallId)
                ->where('caller_type', $receiverType)
                ->where('caller_id', $receiverId)
                ->where('receiver_type', 'customer')
                ->where('receiver_id', $customerId)
                ->update(['call_status' => 'connected']);
        }

        // Check for answer from receiver (format: call_answer_{receiverType}_{receiverId}_{customerId})
        $cacheKey = "call_answer_{$receiverType}_{$receiverId}_{$customerId}";
        $answer = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if ($answer) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            return response()->json(['answer' => $answer]);
        }

        return response()->json(['answer' => null]);
    }

    public function callIce(Request $request)
    {
        $customerId = Session::get('customer_id');
        if (!$customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $receiverType = $request->receiver_type;
        $receiverId = $request->receiver_id;

        // If candidate is provided, store it (customer sending to vendor/freelancer)
        if ($request->has('candidate')) {
            $candidate = $request->candidate;
            $cacheKey = "call_ice_customer_to_{$receiverType}_{$receiverId}_{$customerId}";
            $candidates = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
            $candidates[] = $candidate;
            \Illuminate\Support\Facades\Cache::put($cacheKey, $candidates, 60);
            return response()->json(['success' => true]);
        }

        // Otherwise, retrieve ICE candidates from vendor/freelancer
        $cacheKey = "call_ice_{$receiverType}_{$receiverId}_{$customerId}";
        $candidates = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

        if (!empty($candidates)) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            return response()->json(['candidates' => $candidates]);
        }

        return response()->json(['candidates' => []]);
    }

    public function callEnd(Request $request)
    {
        $customerId = Session::get('customer_id');
        if (!$customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $receiverType = $request->receiver_type;
        $receiverId = $request->receiver_id;

        // Update call status to ended and calculate duration
        $callIdCacheKey = "call_id_customer_{$customerId}_{$receiverType}_{$receiverId}";
        $callId = \Illuminate\Support\Facades\Cache::get($callIdCacheKey);
        if ($callId) {
            $call = CustomerChatCall::find($callId);
            if ($call) {
                $call->call_status = 'ended';
                $call->call_ended_at = now();
                if ($call->call_started_at) {
                    $call->call_duration = $call->call_started_at->diffInSeconds(now());
                }
                $call->save();
                \Illuminate\Support\Facades\Cache::forget($callIdCacheKey);
            }
        }

        // Also check for incoming call from vendor/freelancer
        $incomingCallIdCacheKey = "call_id_{$receiverType}_{$receiverId}_customer_{$customerId}";
        $incomingCallId = \Illuminate\Support\Facades\Cache::get($incomingCallIdCacheKey);
        if ($incomingCallId) {
            $call = CustomerChatCall::find($incomingCallId);
            if ($call) {
                $call->call_status = 'ended';
                $call->call_ended_at = now();
                if ($call->call_started_at) {
                    $call->call_duration = $call->call_started_at->diffInSeconds(now());
                }
                $call->save();
                \Illuminate\Support\Facades\Cache::forget($incomingCallIdCacheKey);
            }
        }

        // Clear all call-related cache
        $keys = [
            "call_offer_{$receiverType}_{$receiverId}_{$customerId}",
            "call_answer_{$receiverType}_{$receiverId}_{$customerId}",
            "call_ice_{$receiverType}_{$receiverId}_{$customerId}",
            "call_ice_customer_to_{$receiverType}_{$receiverId}_{$customerId}"
        ];
        foreach ($keys as $key) {
            \Illuminate\Support\Facades\Cache::forget($key);
        }

        return response()->json(['success' => true]);
    }

    public function updateProfileImage(Request $request)
    {
        $customerId = Session::get('customer_id');
        $customerType = Session::get('customer_type', 'lead');
        
        if (!$customerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$request->hasFile('profile_image')) {
            return response()->json(['success' => false, 'message' => 'No image file found']);
        }

        $file = $request->file('profile_image');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('public/profile_images', $filename);

        // Get customer model
        if ($customerType === 'operation_lead') {
            $customer = OperationLead::find($customerId);
        } else {
            $customer = Lead::find($customerId);
        }

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found']);
        }

        // Delete old profile image if exists
        if ($customer->profile_image) {
            Storage::disk('public')->delete($customer->profile_image);
        }

        // Update profile image
        $customer->profile_image = 'profile_images/' . $filename;
        $customer->save();

        $profileImageUrl = Storage::url('profile_images/' . $filename);

        return response()->json([
            'success' => true,
            'message' => 'Profile image updated successfully',
            'profile_image_url' => $profileImageUrl
        ]);
    }

    public function submitServiceRequest(Request $request)
    {
        $customerId = Session::get('customer_id');
        $customerType = Session::get('customer_type', 'lead');
        
        if (!$customerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Get customer data
        if ($customerType === 'operation_lead') {
            $customer = OperationLead::find($customerId);
        } else {
            $customer = Lead::find($customerId);
        }
        
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
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

        // Create lead
        $lead = Lead::create([
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
            'lead_source' => 'crm',
            'status' => 'follow-up',
            'stage' => 'active',
        ]);

        // Add executive to WhatsApp group if contact number exists
        if ($request->input('contact_no')) {
            $number = $request->input('contact_no');
            $execId = $getUser->id;
            $group = WhatsappMsgGroup::where('whatsapp_number', $number)->first();
            if ($group) {
                $ids = array_filter(explode(',', $group->executive_ids));
                if (!in_array($execId, $ids)) {
                    $ids[] = $execId;
                    $group->executive_ids = implode(',', $ids);
                    $group->save();
                }
            } else {
                WhatsappMsgGroup::create([
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
        $ctx = $this->webSessionCustomerContext();
        if (! $ctx) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        [$customerModel, $customerType, $sessionCustomerId] = $ctx;

        $validated = $request->validate([
            'operation_lead_id' => 'required|integer|exists:operation_leads,id',
            'assignment_type' => 'required|in:vendor,freelancer',
            'assignment_id' => 'required|integer|min:1',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $operationLeadIds = $this->webOperationLeadIdsForChats($customerModel, $sessionCustomerId, $customerType);
        if (! in_array((int) $validated['operation_lead_id'], $operationLeadIds, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid operation lead for this customer'], 403);
        }

        $assignmentQuery = OperationDeploymentDetails::where('operation_lead_id', $validated['operation_lead_id']);
        if ($validated['assignment_type'] === 'vendor') {
            $assignmentQuery->where('vendor_id', $validated['assignment_id']);
        } else {
            $assignmentQuery->where('freelance_staff_id', $validated['assignment_id']);
        }
        if (! $assignmentQuery->exists()) {
            return response()->json(['success' => false, 'message' => 'Assignment not found for this lead'], 404);
        }

        $lead = OperationLead::find($validated['operation_lead_id']);
        if (! $lead || ! $lead->customer_location_attendance_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Pehle is card par "Attendance mark" enable karein, tabhi location share se attendance complete hogi.',
            ], 422);
        }

        $attendance = DeploymentLocationAttendance::firstOrNew([
            'operation_lead_id' => $validated['operation_lead_id'],
            'vendor_id' => $validated['assignment_type'] === 'vendor' ? $validated['assignment_id'] : null,
            'freelancer_id' => $validated['assignment_type'] === 'freelancer' ? $validated['assignment_id'] : null,
            'attendance_date' => now()->toDateString(),
        ]);

        $providerCaptured = false;
        if ($validated['assignment_type'] === 'vendor') {
            $providerCaptured = (bool) $attendance->vendor_location_captured_at;
        } else {
            $providerCaptured = (bool) ($attendance->freelancer_location_captured_at && $attendance->freelancer_selfie_path);
        }
        if (! $providerCaptured) {
            return response()->json([
                'success' => false,
                'message' => $validated['assignment_type'] === 'vendor'
                    ? 'Vendor ne abhi location share nahi ki.'
                    : 'Freelancer ne abhi location + selfie complete nahi kiya. Assigned leads se "Location + selfie" complete hone ka wait karein.',
            ], 422);
        }

        $attendance->customer_latitude = $validated['latitude'];
        $attendance->customer_longitude = $validated['longitude'];
        $attendance->customer_location_captured_at = now();

        if ($providerCaptured) {
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
                ? 'Attendance marked successfully.'
                : 'Customer location captured successfully.',
            'attendance_status' => $attendance->attendance_status,
            'is_location_matched' => (bool) $attendance->is_location_matched,
            'distance_meters' => $attendance->distance_meters,
        ]);
    }

    /**
     * Customer toggles consent for location-based attendance for an operation lead (Your Requirement cards).
     */
    public function setLocationAttendanceEnabled(Request $request)
    {
        $ctx = $this->webSessionCustomerContext();
        if (! $ctx) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        [$customerModel, $customerType, $sessionCustomerId] = $ctx;

        $validated = $request->validate([
            'operation_lead_id' => 'required|integer|exists:operation_leads,id',
            'enabled' => 'required|boolean',
        ]);

        $operationLeadIds = $this->webOperationLeadIdsForChats($customerModel, $sessionCustomerId, $customerType);
        if (! in_array((int) $validated['operation_lead_id'], $operationLeadIds, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid operation lead for this customer'], 403);
        }

        $lead = OperationLead::findOrFail($validated['operation_lead_id']);
        $lead->customer_location_attendance_enabled = $validated['enabled'];
        $lead->save();

        return response()->json([
            'success' => true,
            'message' => $validated['enabled']
                ? 'Location attendance enabled. Freelancer ab location + selfie bhej sakta hai.'
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

    public function paymentDetails()
    {
        $customerId = Session::get('customer_id');
        $customerType = Session::get('customer_type', 'lead');
        
        if (!$customerId) {
            return redirect()->route('home')->with('error', 'Please login to access payment details.');
        }

        // Get customer data
        $customer = null;
        $contactNo = null;
        
        if ($customerType === 'operation_lead') {
            $customer = OperationLead::find($customerId);
            $contactNo = $customer->contact_no ?? null;
        } else {
            $customer = Lead::find($customerId);
            $contactNo = $customer->contact_no ?? null;
        }

        if (!$customer) {
            return redirect()->route('customer.dashboard')->with('error', 'Customer not found.');
        }

        // Get all OperationLead IDs for this customer (by contact_no)
        $operationLeadIds = OperationLead::where('contact_no', $contactNo)->pluck('id')->toArray();
        
        // Also check if customerId itself is an OperationLead ID
        if (!in_array($customerId, $operationLeadIds)) {
            $operationLeadIds[] = $customerId;
        }

        // Get all payment invoices for this customer
        $paymentInvoices = PaymentInvoice::whereIn('operation_lead_id', $operationLeadIds)
            ->with(['operationLead', 'receivedPayments'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate totals
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

        // Get all received payments for history
        $receivedPayments = ReceivedPayment::whereIn('operation_lead_id', $operationLeadIds)
            ->with(['paymentInvoice', 'operationLead'])
            ->orderBy('received_date', 'desc')
            ->get();

        $customerName = $customer->customer_name ?? 'N/A';
        $page_heading = 'Payment Details';

        return view('customer.payment-details', compact(
            'customer',
            'customerName',
            'customerType',
            'paymentInvoices',
            'receivedPayments',
            'totalInvoiceAmount',
            'totalReceivedAmount',
            'totalOutstandingAmount',
            'totalAdvanceAmount',
            'page_heading'
        ));
    }

    /**
     * Show a single payment invoice (same layout as admin payment_invoice_show).
     * Only allows viewing invoices that belong to this customer's operation leads.
     */
    public function showPaymentInvoice($id)
    {
        $customerId = Session::get('customer_id');
        $contactNo = Session::get('contact_no');

        if (!$customerId) {
            return redirect()->route('home')->with('error', 'Please login to view invoice.');
        }

        $customer = OperationLead::find($customerId) ?? Lead::find($customerId);
        if (!$customer) {
            return redirect()->route('customer.dashboard')->with('error', 'Customer not found.');
        }
        $contactNo = $contactNo ?? $customer->contact_no ?? null;
        if (!$contactNo) {
            $operationLeadIds = $customer instanceof OperationLead ? [$customerId] : [];
        } else {
            $operationLeadIds = OperationLead::where('contact_no', $contactNo)->pluck('id')->toArray();
            if ($customer instanceof OperationLead && !in_array($customerId, $operationLeadIds)) {
                $operationLeadIds[] = $customerId;
            }
        }
        $operationLeadIds = array_values(array_unique($operationLeadIds));
        if (empty($operationLeadIds)) {
            return redirect()->route('customer.payment-details')->with('error', 'No invoices found.');
        }

        $invoice = PaymentInvoice::with(['operationLead', 'receivedPayments'])
            ->whereIn('operation_lead_id', $operationLeadIds)
            ->findOrFail($id);

        $totalReceived = (float) $invoice->receivedPayments->sum('amount');
        $paymentAmount = (float) $invoice->payment_amount;
        if ($totalReceived >= $paymentAmount) {
            $status = 'paid';
        } elseif ($totalReceived > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'unpaid';
        }

        if (request()->query('download') === '1') {
            $forPdf = true;
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.operation_leads.payment_invoice_show', compact('invoice', 'totalReceived', 'status', 'forPdf'));
            return $pdf->download('invoice-' . preg_replace('/[^a-zA-Z0-9\-_]/', '-', $invoice->invoice_id) . '.pdf');
        }

        return view('admin.operation_leads.payment_invoice_show', compact('invoice', 'totalReceived', 'status'));
    }

    /**
     * @return array{0: \App\Models\Lead|\App\Models\OperationLead, 1: string, 2: int}|null
     */
    private function webSessionCustomerContext(): ?array
    {
        $customerId = Session::get('customer_id');
        $contactNo = Session::get('contact_no');
        if (! $customerId && ! $contactNo) {
            return null;
        }
        $customerType = Session::get('customer_type', 'lead');
        $customerModel = null;
        if ($customerId) {
            $customerModel = $customerType === 'operation_lead'
                ? OperationLead::find($customerId)
                : Lead::find($customerId);
        }
        if (! $customerModel && $contactNo) {
            $n = ConsultationWebsiteBooking::normalizeContactToTenDigits((string) $contactNo) ?: $contactNo;
            $ol = OperationLead::where('contact_no', $n)->first();
            if ($ol) {
                $customerModel = $ol;
                $customerType = 'operation_lead';
            } else {
                $lead = Lead::where('contact_no', $n)->first();
                if ($lead) {
                    $customerModel = $lead;
                    $customerType = 'lead';
                }
            }
        }
        if (! $customerModel) {
            return null;
        }
        $sessionCustomerId = (int) ($customerId ?? $customerModel->id);

        return [$customerModel, $customerType, $sessionCustomerId];
    }

    /**
     * @param  \App\Models\Lead|\App\Models\OperationLead  $customer
     * @return array<int>
     */
    private function webOperationLeadIdsForChats($customer, int $sessionCustomerId, string $customerType): array
    {
        $contactNo = Session::get('contact_no') ?? ($customer->contact_no ?? null);
        if ($contactNo) {
            $n = ConsultationWebsiteBooking::normalizeContactToTenDigits((string) $contactNo);
            $contactNo = $n ?: $contactNo;
        }

        $ids = [];
        if ($contactNo) {
            $ids = OperationLead::where('contact_no', $contactNo)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        }

        if ($customerType === 'operation_lead' && $sessionCustomerId > 0) {
            if (! in_array($sessionCustomerId, $ids, true)) {
                $ids[] = $sessionCustomerId;
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

        return array_values(array_unique(array_filter($ids, fn ($id) => (int) $id > 0)));
    }

    /**
     * @param  \App\Models\Lead|\App\Models\OperationLead  $customer
     */
    private function webPrimaryOperationLeadIdForMessaging($customer, int $sessionCustomerId, string $customerType, array $operationLeadIds): ?int
    {
        if (! empty($operationLeadIds)) {
            return (int) $operationLeadIds[0];
        }
        $contactNo = Session::get('contact_no') ?? ($customer->contact_no ?? null);
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

    /**
     * Translate text using Gemini 2.0 Flash (for customer chats)
     */
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
