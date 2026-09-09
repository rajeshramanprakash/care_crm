<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ConsultationSlotUnavailableAfterPaymentException;
use App\Facades\UserAssignment;
use App\Http\Controllers\Controller;
use App\Models\ConsultationWebsiteBooking;
use App\Models\DoctorConsultationService;
use App\Models\DoctorConsultationServiceSubService;
use App\Models\DoctorRequest;
use App\Models\Location;
use App\Models\OperationLead;
use App\Models\User;
use App\Services\ConsultationLocationPricingService;
use App\Services\DoctorCalendarAvailabilityService;
use App\Services\DoctorReferralCommissionService;
use App\Services\EasebuzzPaymentService;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PublicConsultationBookingController extends Controller
{
    public function __construct(
        private DoctorCalendarAvailabilityService $calendarService,
        private EasebuzzPaymentService $easebuzzPaymentService,
        private DoctorReferralCommissionService $referralCommissionService,
        private ConsultationLocationPricingService $locationPricing
    ) {}

    public function store(Request $request): JsonResponse
    {
        $prep = $this->validateBookingRequest($request);
        if ($prep instanceof JsonResponse) {
            return $prep;
        }
        ['data' => $data, 'doctor' => $doctor, 'service' => $service, 'durationMinutes' => $durationMinutes, 'startTime' => $startTime, 'endTime' => $endTime, 'bookingFeeAmount' => $bookingFeeAmount] = $prep;

        if ($this->requiresEasebuzzCheckout($bookingFeeAmount)) {
            return response()->json([
                'success' => false,
                'message' => 'This consultation requires online payment. Please complete checkout.',
                'payment_required' => true,
            ], 422);
        }

        $booking = DB::transaction(function () use ($data, $durationMinutes, $startTime, $endTime, $doctor, $service, $bookingFeeAmount) {
            $bookingTimezone = config('app.timezone', 'Asia/Kolkata');
            $meetingStartsAt = Carbon::createFromFormat('Y-m-d H:i', $data['appointment_date'].' '.$startTime, $bookingTimezone);
            $meetingEndsAt = (clone $meetingStartsAt)->addMinutes($durationMinutes);
            $windowStillFree = $this->calendarService->isAppointmentWindowAvailable(
                $doctor->id,
                $data['appointment_date'],
                $startTime,
                $durationMinutes
            );
            if (! $windowStillFree) {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'message' => 'This slot was just booked. Please choose another time.',
                ], 422));
            }

            $booking = ConsultationWebsiteBooking::create(array_merge([
                'doctor_request_id' => $data['doctor_request_id'],
                'doctor_consultation_service_id' => $data['doctor_consultation_service_id'] ?? null,
                'customer_name' => trim($data['customer_name']),
                'contact_no' => trim($data['contact_no']),
                'customer_address' => trim((string) ($data['customer_address'] ?? '')) ?: null,
                'customer_city' => trim((string) ($data['customer_city'] ?? '')) ?: null,
                'customer_address_lat' => $data['customer_address_lat'] ?? null,
                'customer_address_lng' => $data['customer_address_lng'] ?? null,
                'consultation_mode' => $data['consultation_mode'],
                'booking_fee_amount' => $bookingFeeAmount,
                'consultation_duration_minutes' => $durationMinutes,
                'appointment_date' => $data['appointment_date'],
                'appointment_start_time' => $startTime,
                'appointment_end_time' => $endTime,
                'payment_status' => 'paid',
                'paid_at' => now(),
            ], $this->serviceSelectionFields($data)));
            if (($data['consultation_mode'] ?? '') === 'online') {
                $meeting = $this->createOnlineMeetingLink(
                    $doctor,
                    $service?->name,
                    $data,
                    $meetingStartsAt,
                    $durationMinutes
                );
                $booking->online_meeting_provider = $meeting['provider'] ?? null;
                $booking->online_meeting_link = $meeting['link'] ?? null;
                $booking->online_meeting_starts_at = $meetingStartsAt;
                $booking->online_meeting_ends_at = $meetingEndsAt;
                $booking->save();
            }

            $operationLeadResult = $this->createOperationLeadForBooking($data, $service?->name);
            $operationLead = $operationLeadResult['lead'] ?? null;
            if ($operationLead) {
                $booking->operation_lead_id = $operationLead->id;
                $booking->save();
            }
            $booking->setAttribute('assigned_executive_name', $operationLeadResult['executive_name'] ?? null);

            return $booking;
        });

        $this->referralCommissionService->recordForPaidBooking($booking->fresh());

        return response()->json($this->bookingSuccessPayload($booking));
    }

    /**
     * Start Easebuzz hosted pay for a website consultation that has a positive fee.
     */
    public function easebuzzInit(Request $request): JsonResponse
    {
        $this->calendarService->cleanupStalePendingPaymentBookings();

        $prep = $this->validateBookingRequest($request);
        if ($prep instanceof JsonResponse) {
            return $prep;
        }
        ['data' => $data, 'doctor' => $doctor, 'service' => $service, 'durationMinutes' => $durationMinutes, 'startTime' => $startTime, 'endTime' => $endTime, 'bookingFeeAmount' => $bookingFeeAmount] = $prep;

        if (! $this->requiresEasebuzzCheckout($bookingFeeAmount)) {
            return response()->json([
                'success' => true,
                'skip_payment' => true,
                'message' => 'No payable fee for this booking; submit without payment gateway.',
            ]);
        }

        if (! $this->easebuzzPaymentService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Online payment is not configured yet. Please try again later or contact support.',
            ], 503);
        }

        $booking = DB::transaction(function () use ($data, $durationMinutes, $startTime, $endTime, $doctor, $service, $bookingFeeAmount) {
            $windowStillFree = $this->calendarService->isAppointmentWindowAvailable(
                $doctor->id,
                $data['appointment_date'],
                $startTime,
                $durationMinutes
            );
            if (! $windowStillFree) {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'message' => 'This slot was just booked. Please choose another time.',
                ], 422));
            }

            // Booking row does not exist yet — do not use $booking->id here.
            $txnid = 'CWB'.str_pad((string) $doctor->id, 4, '0', STR_PAD_LEFT).strtoupper(bin2hex(random_bytes(6)));

            return ConsultationWebsiteBooking::create(array_merge([
                'doctor_request_id' => $data['doctor_request_id'],
                'doctor_consultation_service_id' => $data['doctor_consultation_service_id'] ?? null,
                'customer_name' => trim($data['customer_name']),
                'contact_no' => trim($data['contact_no']),
                'customer_address' => trim((string) ($data['customer_address'] ?? '')) ?: null,
                'customer_city' => trim((string) ($data['customer_city'] ?? '')) ?: null,
                'customer_address_lat' => $data['customer_address_lat'] ?? null,
                'customer_address_lng' => $data['customer_address_lng'] ?? null,
                'consultation_mode' => $data['consultation_mode'],
                'booking_fee_amount' => $bookingFeeAmount,
                'consultation_duration_minutes' => $durationMinutes,
                'appointment_date' => $data['appointment_date'],
                'appointment_start_time' => $startTime,
                'appointment_end_time' => $endTime,
                'payment_status' => 'pending_payment',
                'easebuzz_txnid' => $txnid,
            ], $this->serviceSelectionFields($data)));
        });

        $publicBase = $this->publicCallbackBaseUrl();
        $returnUrl = $publicBase.'/api/public/consultation-booking/easebuzz-return';

        $amountStr = sprintf('%.2f', (float) $bookingFeeAmount);
        $phoneDigits = preg_replace('/\D/', '', (string) $booking->contact_no);
        if (strlen($phoneDigits) >= 10) {
            $phoneDigits = substr($phoneDigits, -10);
        } elseif ($phoneDigits !== '') {
            $phoneDigits = str_pad($phoneDigits, 10, '0', STR_PAD_LEFT);
        } else {
            $phoneDigits = '9999999999';
        }

        $email = trim((string) config('services.easebuzz.placeholder_email', 'payments@example.com'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = 'payments@example.com';
        }

        $productinfo = 'Doctor consultation booking';
        if ($service?->name) {
            $productinfo = 'Consultation '.$service->name;
        }
        $productinfo = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $productinfo);
        $productinfo = trim(preg_replace('/\s+/', ' ', $productinfo));
        if ($productinfo === '') {
            $productinfo = 'Consultation';
        }
        $productinfo = function_exists('mb_substr') ? mb_substr($productinfo, 0, 100) : substr($productinfo, 0, 100);

        $firstName = trim((string) $booking->customer_name);
        $firstName = function_exists('mb_substr') ? mb_substr($firstName, 0, 60) : substr($firstName, 0, 60);

        $addr = trim((string) ($booking->customer_address ?? ''));
        if ($addr === '') {
            $addr = 'NA';
        }
        $city = trim((string) ($booking->customer_city ?? ''));
        if ($city === '') {
            $city = 'NA';
        }
        $addr1 = function_exists('mb_substr') ? mb_substr($addr, 0, 100) : substr($addr, 0, 100);
        $addr2 = '';

        $init = $this->easebuzzPaymentService->initiatePaymentLink([
            'txnid' => (string) $booking->easebuzz_txnid,
            'amount' => $amountStr,
            'productinfo' => $productinfo,
            'firstname' => $firstName,
            'email' => $email,
            'phone' => $phoneDigits,
            'surl' => $returnUrl,
            'furl' => $returnUrl,
            'udf1' => (string) $booking->id,
            'address1' => $addr1,
            'address2' => $addr2,
            'city' => function_exists('mb_substr') ? mb_substr($city, 0, 50) : substr($city, 0, 50),
            'state' => function_exists('mb_substr') ? mb_substr($city, 0, 50) : substr($city, 0, 50),
            'country' => 'India',
            'zipcode' => '110001',
        ]);

        if (! $init['ok']) {
            $booking->delete();

            $payload = [
                'success' => false,
                'message' => $init['error'] ?? 'Could not start payment.',
            ];
            if (config('app.debug') && isset($init['raw'])) {
                $payload['easebuzz_response'] = $init['raw'];
            }

            return response()->json($payload, 422);
        }

        return response()->json([
            'success' => true,
            'skip_payment' => false,
            'payment_url' => $init['payment_url'] ?? null,
            'booking_id' => $booking->id,
            'easebuzz_txnid' => (string) $booking->easebuzz_txnid,
            'booking_fee_amount' => (float) $amountStr,
        ]);
    }

    /**
     * Careweb polls this after opening the Easebuzz window so thank-you shows even if postMessage fails.
     */
    public function paymentPoll(string $txnid): JsonResponse
    {
        $txnid = trim($txnid);
        if ($txnid === '' || strlen($txnid) > 64 || ! preg_match('/^[A-Za-z0-9]+$/', $txnid)) {
            return response()->json(['success' => false, 'message' => 'Invalid transaction reference.'], 422);
        }

        $booking = ConsultationWebsiteBooking::query()->where('easebuzz_txnid', $txnid)->first();
        if (! $booking) {
            return response()->json([
                'success' => true,
                'paid' => false,
                'not_found' => true,
            ]);
        }

        if ($booking->payment_status !== 'paid') {
            return response()->json([
                'success' => true,
                'paid' => false,
            ]);
        }

        $booking->refresh();

        return response()->json(array_merge([
            'success' => true,
            'paid' => true,
            'type' => 'carelix_easebuzz',
            'status' => 'success',
        ], $this->bookingSuccessPayload($booking)));
    }

    /**
     * Browser return from Easebuzz (success or failure). Notifies opener via postMessage and closes the window.
     */
    public function easebuzzReturn(Request $request)
    {
        $salt = trim((string) config('services.easebuzz.salt', ''));
        $raw = array_merge($request->query(), $request->request->all());
        $payload = [];
        foreach ($raw as $k => $v) {
            if (is_array($v)) {
                continue;
            }
            $payload[strtolower((string) $k)] = (string) $v;
        }

        $html = function (array $msg) {
            $json = json_encode($msg, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

            return <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Payment</title></head>
<body>
<p style="font-family:system-ui,sans-serif;padding:16px;">Payment complete. You can return to Carelix.</p>
<script>
(function () {
  var msg = {$json};
  var targets = [];
  try {
    if (window.opener) targets.push(window.opener);
  } catch (e) {}
  try {
    if (window.parent && window.parent !== window) targets.push(window.parent);
  } catch (e) {}
  targets.forEach(function (w) {
    try { w.postMessage(msg, '*'); } catch (e2) {}
  });
  setTimeout(function () {
    try {
      if (window.opener) window.close();
    } catch (e3) {}
  }, 400);
})();
</script>
</body></html>
HTML;
        };

        if ($salt === '' || ! $this->easebuzzPaymentService->isConfigured()) {
            return response($html([
                'type' => 'carelix_easebuzz',
                'status' => 'error',
                'message' => 'Payment gateway is not configured.',
            ]), 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        if (! $this->easebuzzPaymentService->verifyResponseHash($payload, $salt)) {
            Log::warning('Easebuzz return hash mismatch', ['txnid' => $payload['txnid'] ?? null]);

            return response($html([
                'type' => 'carelix_easebuzz',
                'status' => 'error',
                'message' => 'Payment verification failed.',
            ]), 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $txnid = trim((string) ($payload['txnid'] ?? ''));
        $booking = $txnid !== '' ? ConsultationWebsiteBooking::query()->where('easebuzz_txnid', $txnid)->first() : null;
        if (! $booking) {
            return response($html([
                'type' => 'carelix_easebuzz',
                'status' => 'error',
                'message' => 'Booking not found for this payment.',
            ]), 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $gatewayStatus = strtolower(trim((string) ($payload['status'] ?? '')));
        $expectedAmount = $booking->booking_fee_amount !== null ? sprintf('%.2f', (float) $booking->booking_fee_amount) : null;
        $paidAmount = isset($payload['amount']) ? sprintf('%.2f', (float) $payload['amount']) : null;

        if ($gatewayStatus === 'failure') {
            if ($booking->payment_status === 'pending_payment') {
                $booking->delete();
            }

            return response($html([
                'type' => 'carelix_easebuzz',
                'status' => 'failure',
                'message' => 'Payment was not completed.',
            ]), 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        if ($gatewayStatus !== 'success') {
            return response($html([
                'type' => 'carelix_easebuzz',
                'status' => 'error',
                'message' => 'Unexpected payment status.',
            ]), 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        if ($expectedAmount !== null && $paidAmount !== null && $expectedAmount !== $paidAmount) {
            Log::warning('Easebuzz amount mismatch', [
                'booking_id' => $booking->id,
                'expected' => $expectedAmount,
                'paid' => $paidAmount,
            ]);

            return response($html([
                'type' => 'carelix_easebuzz',
                'status' => 'error',
                'message' => 'Paid amount does not match booking fee.',
            ]), 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        try {
            $this->finalizePaidConsultationBooking($booking);
        } catch (ConsultationSlotUnavailableAfterPaymentException $e) {
            Log::warning('Consultation slot unavailable after payment', [
                'booking_id' => $booking->id,
                'txnid' => $txnid,
                'error' => $e->getMessage(),
            ]);

            return response($html([
                'type' => 'carelix_easebuzz',
                'status' => 'error',
                'message' => 'Payment received but this time slot is no longer available. Please contact support with your Easebuzz transaction id for assistance.',
                'txnid' => $txnid,
            ]), 200)->header('Content-Type', 'text/html; charset=UTF-8');
        } catch (\Throwable $e) {
            Log::error('Finalize consultation booking after payment failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return response($html([
                'type' => 'carelix_easebuzz',
                'status' => 'error',
                'message' => 'Payment received but booking confirmation failed. Please contact support with your transaction id.',
                'txnid' => $txnid,
            ]), 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $booking->refresh();

        return response($html(array_merge([
            'type' => 'carelix_easebuzz',
            'status' => 'success',
            'message' => 'Thank you. Your booking is confirmed.',
        ], $this->bookingSuccessPayload($booking))), 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function requiresEasebuzzCheckout(?float $bookingFeeAmount): bool
    {
        if ($bookingFeeAmount === null) {
            return false;
        }

        return round($bookingFeeAmount, 2) > 0;
    }

    private function publicCallbackBaseUrl(): string
    {
        $u = rtrim(trim((string) env('CARECRM_PUBLIC_URL', '')), '/');
        if ($u === '') {
            $u = rtrim((string) config('app.url', ''), '/');
        }

        return $u;
    }

    /**
     * @return array{data: array, doctor: DoctorRequest, service: ?DoctorConsultationService, durationMinutes: int, startTime: string, endTime: string, bookingFeeAmount: ?float}|JsonResponse
     */
    private function validateBookingRequest(Request $request): array|JsonResponse
    {
        $data = $request->validate([
            'doctor_request_id' => ['required', 'integer', 'exists:doctor_requests,id'],
            'doctor_consultation_service_id' => ['required', 'integer', 'exists:doctor_consultation_services,id'],
            'sub_service_id' => ['nullable', 'integer', 'exists:doctor_consultation_service_sub_services,id'],
            'sub_service_name' => ['nullable', 'string', 'max:255'],
            'selected_tags' => ['nullable', 'array', 'max:30'],
            'selected_tags.*' => ['string', 'max:120'],
            'customer_name' => ['required', 'string', 'max:255'],
            'contact_no' => ['required', 'string', 'max:32'],
            'customer_address' => ['required', 'string', 'max:500'],
            'customer_city' => ['required', 'string', 'max:120'],
            'customer_address_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'customer_address_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'consultation_mode' => ['required', 'string', Rule::in(['online', 'home_visit', 'clinic_visit'])],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_start_time' => ['required', 'string', 'size:5'],
        ]);

        $doctor = DoctorRequest::query()->whereKey($data['doctor_request_id'])->first();
        if (! $doctor || strtolower((string) $doctor->approval_status) !== 'approved') {
            return response()->json(['success' => false, 'message' => 'This doctor is not available for booking.'], 422);
        }

        $service = DoctorConsultationService::query()
            ->whereKey((int) $data['doctor_consultation_service_id'])
            ->first();
        $durationMinutes = max(1, (int) ($service?->consultation_duration_minutes ?? 30));
        $startTime = $data['appointment_start_time'];
        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $startTime)) {
            return response()->json(['success' => false, 'message' => 'Invalid appointment start time.'], 422);
        }
        $endMinutes = $this->calendarService->timeToMinutes($startTime) + $durationMinutes;
        if ($endMinutes > 24 * 60) {
            return response()->json(['success' => false, 'message' => 'Selected time is invalid for this service duration.'], 422);
        }
        $endTime = $this->calendarService->minutesToTime($endMinutes);

        $isWindowAvailable = $this->calendarService->isAppointmentWindowAvailable(
            $doctor->id,
            $data['appointment_date'],
            $startTime,
            $durationMinutes
        );
        if (! $isWindowAvailable) {
            return response()->json([
                'success' => false,
                'message' => 'Selected time slot is not available. Please choose another slot.',
            ], 422);
        }

        $subServiceId = (int) ($data['sub_service_id'] ?? 0);
        $bookingFeeAmount = $this->locationPricing->patientBookingFeeForMode(
            trim((string) $data['customer_city']),
            (int) $data['doctor_consultation_service_id'],
            $subServiceId > 0 ? $subServiceId : null,
            $doctor,
            (string) $data['consultation_mode']
        );

        $this->normalizeCustomerAddressCoords($data);

        return [
            'data' => $data,
            'doctor' => $doctor,
            'service' => $service,
            'durationMinutes' => $durationMinutes,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'bookingFeeAmount' => $bookingFeeAmount,
        ];
    }

    private function finalizePaidConsultationBooking(ConsultationWebsiteBooking $booking): void
    {
        if ($booking->payment_status === 'paid') {
            return;
        }

        $doctor = DoctorRequest::query()->whereKey($booking->doctor_request_id)->first();
        if (! $doctor) {
            throw new \RuntimeException('Doctor missing for booking.');
        }
        $service = DoctorConsultationService::query()->whereKey((int) ($booking->doctor_consultation_service_id ?? 0))->first();

        $data = [
            'doctor_request_id' => (int) $booking->doctor_request_id,
            'doctor_consultation_service_id' => $booking->doctor_consultation_service_id,
            'customer_name' => (string) $booking->customer_name,
            'contact_no' => (string) $booking->contact_no,
            'customer_address' => (string) ($booking->customer_address ?? ''),
            'customer_city' => (string) ($booking->customer_city ?? ''),
            'customer_address_lat' => $booking->customer_address_lat,
            'customer_address_lng' => $booking->customer_address_lng,
            'consultation_mode' => (string) $booking->consultation_mode,
            'appointment_date' => optional($booking->appointment_date)->format('Y-m-d') ?? '',
            'appointment_start_time' => (string) $booking->appointment_start_time,
        ];

        DB::transaction(function () use ($booking, $doctor, $service, $data) {
            $booking->refresh();
            if ($booking->payment_status === 'paid') {
                return;
            }

            $appointmentDate = optional($booking->appointment_date)->format('Y-m-d') ?? '';
            $startTime = (string) $booking->appointment_start_time;
            $durationMinutes = max(1, (int) $booking->consultation_duration_minutes);

            if ($appointmentDate === '' || $startTime === '') {
                throw new \RuntimeException('Booking appointment date or time is missing.');
            }

            $slotStillFree = $this->calendarService->isAppointmentWindowAvailable(
                (int) $booking->doctor_request_id,
                $appointmentDate,
                $startTime,
                $durationMinutes,
                (int) $booking->id
            );
            if (! $slotStillFree) {
                throw new ConsultationSlotUnavailableAfterPaymentException(
                    'Appointment window is no longer available after payment.'
                );
            }

            $bookingTimezone = config('app.timezone', 'Asia/Kolkata');
            $meetingStartsAt = Carbon::createFromFormat(
                'Y-m-d H:i',
                $appointmentDate.' '.$startTime,
                $bookingTimezone
            );
            $meetingEndsAt = (clone $meetingStartsAt)->addMinutes($durationMinutes);

            $booking->payment_status = 'paid';
            $booking->paid_at = now();
            $booking->save();

            if ($booking->consultation_mode === 'online' && empty($booking->online_meeting_link)) {
                $meeting = $this->createOnlineMeetingLink(
                    $doctor,
                    $service?->name,
                    $data,
                    $meetingStartsAt,
                    $durationMinutes
                );
                $booking->online_meeting_provider = $meeting['provider'] ?? null;
                $booking->online_meeting_link = $meeting['link'] ?? null;
                $booking->online_meeting_starts_at = $meetingStartsAt;
                $booking->online_meeting_ends_at = $meetingEndsAt;
                $booking->save();
            }

            if (! $booking->operation_lead_id) {
                $operationLeadResult = $this->createOperationLeadForBooking($data, $service?->name);
                $operationLead = $operationLeadResult['lead'] ?? null;
                if ($operationLead) {
                    $booking->operation_lead_id = $operationLead->id;
                    $booking->save();
                }
                $booking->setAttribute('assigned_executive_name', $operationLeadResult['executive_name'] ?? null);
            }
        });

        $booking->refresh();
        $this->referralCommissionService->recordForPaidBooking($booking);
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingSuccessPayload(ConsultationWebsiteBooking $booking): array
    {
        $booking->loadMissing(['doctorRequest', 'consultationService', 'consultationSubService']);

        $execName = $booking->getAttribute('assigned_executive_name');
        if (($execName === null || $execName === '') && $booking->operation_lead_id) {
            $lead = OperationLead::query()->find($booking->operation_lead_id);
            if ($lead && $lead->executive) {
                $assignedExecutive = User::query()->find($lead->executive);
                if ($assignedExecutive) {
                    $execName = trim(((string) ($assignedExecutive->f_name ?? '')).' '.((string) ($assignedExecutive->l_name ?? '')));
                    if ($execName === '') {
                        $execName = (string) ($assignedExecutive->name ?? '');
                    }
                }
            }
        }

        $doctor = $booking->doctorRequest;
        $service = $booking->consultationService;
        $appointmentYear = optional($booking->appointment_date)?->format('Y') ?? now()->format('Y');
        $bookingReference = 'CRX-'.$appointmentYear.'-'.$booking->id;

        $ratingRaw = $doctor?->website_card_rating;
        $doctorRating = is_numeric($ratingRaw) ? (float) $ratingRaw : null;

        return [
            'success' => true,
            'message' => 'Thank you. Your booking request has been submitted.',
            'booking_id' => $booking->id,
            'booking_reference' => $bookingReference,
            'booking_fee_amount' => $booking->booking_fee_amount !== null ? (float) $booking->booking_fee_amount : null,
            'operation_lead_id' => $booking->operation_lead_id,
            'assigned_executive_name' => $execName ?: null,
            'appointment_start_time' => $booking->appointment_start_time,
            'appointment_end_time' => $booking->appointment_end_time,
            'appointment_date' => optional($booking->appointment_date)?->format('Y-m-d'),
            'consultation_mode' => $booking->consultation_mode,
            'consultation_mode_label' => ConsultationWebsiteBooking::modeLabel((string) ($booking->consultation_mode ?? '')),
            'consultation_duration_minutes' => (int) ($booking->consultation_duration_minutes ?? 0),
            'doctor_name' => $doctor?->name,
            'doctor_qualification' => $doctor?->website_card_qualification,
            'doctor_rating' => $doctorRating,
            'doctor_profile_image_url' => $this->doctorProfileImagePublicUrl($doctor),
            'service_name' => $service?->name,
            'sub_service_name' => $booking->sub_service_name ?: $booking->consultationSubService?->name,
            'selected_tags' => is_array($booking->selected_tags) ? array_values($booking->selected_tags) : [],
            'customer_name' => $booking->customer_name,
            'contact_no' => $booking->contact_no,
            'customer_address' => $booking->customer_address,
            'customer_city' => $booking->customer_city,
            'customer_address_lat' => $booking->customer_address_lat !== null ? (float) $booking->customer_address_lat : null,
            'customer_address_lng' => $booking->customer_address_lng !== null ? (float) $booking->customer_address_lng : null,
            'easebuzz_txnid' => $booking->easebuzz_txnid ? (string) $booking->easebuzz_txnid : null,
            'payment_status' => (string) ($booking->payment_status ?? ''),
            'payment_status_label' => ConsultationWebsiteBooking::paymentStatusLabel($booking->payment_status),
            'online_meeting_link' => $booking->online_meeting_link,
            'online_meeting_provider' => $booking->online_meeting_provider,
            'online_meeting_starts_at' => optional($booking->online_meeting_starts_at)->toIso8601String(),
            'online_meeting_ends_at' => optional($booking->online_meeting_ends_at)->toIso8601String(),
        ];
    }

    private function doctorProfileImagePublicUrl(?DoctorRequest $doctor): ?string
    {
        if ($doctor === null) {
            return null;
        }
        $path = trim((string) ($doctor->profile_image ?? ''));
        if ($path === '') {
            return null;
        }
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function serviceSelectionFields(array $data): array
    {
        $subId = (int) ($data['sub_service_id'] ?? 0);
        $subName = trim((string) ($data['sub_service_name'] ?? ''));
        if ($subId > 0 && $subName === '') {
            $subRow = DoctorConsultationServiceSubService::query()->whereKey($subId)->first();
            $subName = trim((string) ($subRow?->name ?? ''));
        }

        $tagsRaw = $data['selected_tags'] ?? [];
        $tags = [];
        if (is_array($tagsRaw)) {
            foreach ($tagsRaw as $tag) {
                $t = trim((string) $tag);
                if ($t !== '') {
                    $tags[] = $t;
                }
            }
        }
        $tags = array_values(array_unique($tags));

        return [
            'doctor_consultation_service_sub_service_id' => $subId > 0 ? $subId : null,
            'sub_service_name' => $subName !== '' ? $subName : null,
            'selected_tags' => $tags !== [] ? $tags : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function normalizeCustomerAddressCoords(array &$data): void
    {
        $lat = $data['customer_address_lat'] ?? null;
        $lng = $data['customer_address_lng'] ?? null;
        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            $data['customer_address_lat'] = null;
            $data['customer_address_lng'] = null;

            return;
        }
        $data['customer_address_lat'] = round((float) $lat, 7);
        $data['customer_address_lng'] = round((float) $lng, 7);
    }

    private function createOnlineMeetingLink(
        DoctorRequest $doctor,
        ?string $serviceName,
        array $data,
        Carbon $meetingStartsAt,
        int $durationMinutes
    ): array {
        $zoomClientId = trim((string) env('ZOOM_CLIENT_ID', ''));
        $zoomClientSecret = trim((string) env('ZOOM_CLIENT_SECRET', ''));
        $zoomAccountId = trim((string) env('ZOOM_ACCOUNT_ID', ''));
        $zoomUserId = trim((string) env('ZOOM_USER_ID', 'me'));
        if ($zoomClientId !== '' && $zoomClientSecret !== '' && $zoomAccountId !== '') {
            try {
                $tokenRes = Http::asForm()
                    ->withBasicAuth($zoomClientId, $zoomClientSecret)
                    ->post('https://zoom.us/oauth/token', [
                        'grant_type' => 'account_credentials',
                        'account_id' => $zoomAccountId,
                    ]);
                if ($tokenRes->successful()) {
                    $token = (string) ($tokenRes->json('access_token') ?? '');
                    if ($token !== '') {
                        $topic = 'Carelix Consultation - '.trim((string) ($doctor->name ?? 'Doctor'));
                        if ($serviceName) {
                            $topic .= ' ('.$serviceName.')';
                        }
                        $meetingRes = Http::withToken($token)
                            ->post('https://api.zoom.us/v2/users/'.urlencode($zoomUserId).'/meetings', [
                                'topic' => $topic,
                                'type' => 2,
                                'start_time' => $meetingStartsAt->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                                'duration' => max(1, $durationMinutes),
                                'timezone' => (string) config('app.timezone', 'Asia/Kolkata'),
                                'settings' => [
                                    'waiting_room' => true,
                                    'join_before_host' => false,
                                ],
                            ]);
                        if ($meetingRes->successful()) {
                            $joinUrl = trim((string) ($meetingRes->json('join_url') ?? ''));
                            if ($joinUrl !== '') {
                                return [
                                    'provider' => 'zoom',
                                    'link' => $joinUrl,
                                ];
                            }
                        } else {
                            Log::warning('Zoom meeting create failed for website consultation booking', [
                                'status' => $meetingRes->status(),
                                'body' => $meetingRes->body(),
                            ]);
                        }
                    }
                } else {
                    Log::warning('Zoom token create failed for website consultation booking', [
                        'status' => $tokenRes->status(),
                        'body' => $tokenRes->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Zoom integration exception for website consultation booking', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'provider' => null,
            'link' => null,
        ];
    }

    /**
     * @return array{lead:?OperationLead, executive_name:?string}
     */
    private function createOperationLeadForBooking(array $data, ?string $serviceName): array
    {
        try {
            $city = trim((string) ($data['customer_city'] ?? ''));
            $location = null;
            if ($city !== '') {
                $location = Location::query()
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($city)])
                    ->first();
            }
            $assignedExecutive = UserAssignment::getAssigningUser(4, $location?->id);
            if (! $assignedExecutive) {
                $assignedExecutive = User::query()
                    ->whereRaw('FIND_IN_SET(role_id, "4")')
                    ->orderBy('id')
                    ->first();
            }
            $nextNumber = ((int) (OperationLead::query()->max('id') ?? 0)) + 1;

            $addrBase = trim((string) ($data['customer_address'] ?? ''));
            $latRaw = $data['customer_address_lat'] ?? null;
            $lngRaw = $data['customer_address_lng'] ?? null;
            $mapUrl = null;
            if (is_numeric($latRaw) && is_numeric($lngRaw)) {
                $mapUrl = ConsultationWebsiteBooking::googleMapsOpenUrl((float) $latRaw, (float) $lngRaw);
            }
            if ($addrBase !== '' && $mapUrl) {
                $addrBase .= "\n".$mapUrl;
            } elseif ($addrBase === '' && $mapUrl) {
                $addrBase = $mapUrl;
            }

            $lead = OperationLead::create([
                'lead_id' => 'CHO'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT),
                'date_time' => now(),
                'executive' => $assignedExecutive?->id,
                'customer_name' => trim((string) ($data['customer_name'] ?? '')),
                'contact_no' => trim((string) ($data['contact_no'] ?? '')),
                'address' => $addrBase !== '' ? $addrBase : null,
                'location' => $city !== '' ? $city : null,
                'query' => trim((string) ($serviceName ?? 'Doctor consultation')) ?: 'Doctor consultation',
                'query_remark' => 'Website consultation booking',
                'status' => 'follow-up',
            ]);
            $execName = null;
            if ($assignedExecutive) {
                $execName = trim(((string) ($assignedExecutive->f_name ?? '')).' '.((string) ($assignedExecutive->l_name ?? '')));
                if ($execName === '') {
                    $execName = (string) ($assignedExecutive->name ?? '');
                }
            }

            return [
                'lead' => $lead,
                'executive_name' => $execName ?: null,
            ];
        } catch (\Throwable $e) {
            Log::error('Website booking operation lead create failed', [
                'error' => $e->getMessage(),
                'contact_no' => $data['contact_no'] ?? null,
            ]);

            return [
                'lead' => null,
                'executive_name' => null,
            ];
        }
    }
}
