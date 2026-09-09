<?php

namespace App\Console\Commands;

use App\Models\ConsultationWebsiteBooking;
use App\Services\DoctorReferralCommissionService;
use Illuminate\Console\Command;

class BackfillDoctorReferralCommissions extends Command
{
    protected $signature = 'doctor-referral:backfill-commissions';

    protected $description = 'Create referral commission ledger rows for existing paid website bookings';

    public function handle(DoctorReferralCommissionService $service): int
    {
        $count = 0;
        ConsultationWebsiteBooking::query()
            ->where('payment_status', 'paid')
            ->whereHas('doctorRequest', fn ($q) => $q->whereNotNull('doctor_referral_user_id'))
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use ($service, &$count) {
                foreach ($bookings as $booking) {
                    if ($service->recordForPaidBooking($booking)) {
                        $count++;
                    }
                }
            });

        $this->info("Processed {$count} paid booking(s).");

        return self::SUCCESS;
    }
}
