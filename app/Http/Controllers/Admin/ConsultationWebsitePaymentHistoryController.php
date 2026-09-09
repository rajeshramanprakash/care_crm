<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationWebsiteBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ConsultationWebsitePaymentHistoryController extends Controller
{
    /**
     * Bookings from careweb consultation.php (public API), with payment and appointment context.
     */
    public function index(Request $request)
    {
        $query = ConsultationWebsiteBooking::query()
            ->with([
                'doctorRequest:id,name',
                'consultationService:id,name',
                'consultationSubService:id,name',
            ]);

        $status = $request->query('payment_status', '');
        if ($status === 'paid') {
            $query->where('payment_status', 'paid');
        } elseif ($status === 'pending_payment') {
            $query->where('payment_status', 'pending_payment');
        } elseif ($status === 'with_fee') {
            $query->whereNotNull('booking_fee_amount')
                ->where('booking_fee_amount', '>', 0);
        }

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($w) use ($like, $q) {
                $w->where('customer_name', 'like', $like)
                    ->orWhere('contact_no', 'like', $like)
                    ->orWhere('easebuzz_txnid', 'like', $like)
                    ->orWhere('customer_address', 'like', $like)
                    ->orWhere('customer_city', 'like', $like);
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q);
                }
                $w->orWhereHas('doctorRequest', function ($dr) use ($like) {
                    $dr->where('name', 'like', $like);
                });
            });
        }

        $perPage = (int) $request->query('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $bookings = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        $paidSum = (float) ConsultationWebsiteBooking::query()
            ->where('payment_status', 'paid')
            ->sum('booking_fee_amount');

        $paidThisMonth = (float) ConsultationWebsiteBooking::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', Carbon::now()->startOfMonth())
            ->sum('booking_fee_amount');

        $pendingCount = ConsultationWebsiteBooking::query()
            ->where('payment_status', 'pending_payment')
            ->count();

        return view('admin.consultation_website_payments.index', [
            'bookings' => $bookings,
            'filterPaymentStatus' => $status,
            'searchQ' => $q,
            'paidSum' => $paidSum,
            'paidThisMonth' => $paidThisMonth,
            'pendingCount' => $pendingCount,
            'perPage' => $perPage,
        ]);
    }

    public function viewModal(ConsultationWebsiteBooking $booking)
    {
        $booking->load([
            'doctorRequest:id,name,mobile,contact_no,job_title,city',
            'consultationService:id,name,consultation_duration_minutes',
            'consultationSubService:id,name',
        ]);

        return view('admin.consultation_website_payments.partials.detail_modal_body', [
            'booking' => $booking,
        ]);
    }
}
