<?php

namespace App\Services\Leegality;

use App\Models\JobRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class FreelancerServiceAgreementDocument
{
    /**
     * @return array<string, mixed>
     */
    public function viewData(JobRequest $freelancer, ?string $irn = null): array
    {
        $irn = $irn ?: ('FRL-'.$freelancer->id.'-'.now()->format('YmdHis'));
        $scheduleRows = $this->buildScheduleRows($freelancer);

        return [
            'freelancer' => $freelancer,
            'irn' => $irn,
            'generatedAt' => now(),
            'agreementDate' => now()->format('d M Y'),
            'agreement_number' => $freelancer->agreement_number ?? ('CLX-AGR-FRL-'.now()->format('Y').'-'.str_pad($freelancer->id, 6, '0', STR_PAD_LEFT)),
            'partner_id' => $freelancer->partner_id ?? $freelancer->lead_id ?? ('CLX-FRL-'.str_pad($freelancer->id, 6, '0', STR_PAD_LEFT)),
            'scheduleRows' => $scheduleRows,
        ];
    }

    public function renderHtml(JobRequest $freelancer, ?string $irn = null): string
    {
        return view('pdf.freelancer-service-agreement', $this->viewData($freelancer, $irn))->render();
    }

    public function makePdf(JobRequest $freelancer, ?string $irn = null)
    {
        $html = $this->renderHtml($freelancer, $irn);

        return Pdf::loadHTML($html)->setPaper('a4');
    }

    public function pdfBinary(JobRequest $freelancer, ?string $irn = null): string
    {
        return $this->makePdf($freelancer, $irn)->output();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildScheduleRows(JobRequest $freelancer): array
    {
        $rows = [];
        $freelancer->loadMissing('servicePrices');

        if ($freelancer->servicePrices && $freelancer->servicePrices->count() > 0) {
            foreach ($freelancer->servicePrices as $sp) {
                $rows[] = [
                    'service' => $freelancer->job_title ?: 'Care Service',
                    'sub_service' => $sp->sub_service_name ?? 'General',
                    'city' => $freelancer->city ?: 'All Operating Cities',
                    'shift' => $sp->shift ? ($sp->shift === 'both' ? 'Both 12/24 Hr' : $sp->shift.' Hr') : ($freelancer->shift ?: '12 Hours'),
                    'rate' => $sp->price ? '₹ '.number_format((float) $sp->price, 2) : 'As per RCL',
                    'billed_per' => 'Per shift',
                ];
            }
        }

        if (empty($rows)) {
            $subs = is_array($freelancer->service_sub_services) ? $freelancer->service_sub_services : [];
            if (! empty($subs)) {
                foreach ($subs as $sub) {
                    $rows[] = [
                        'service' => $freelancer->job_title ?: 'Care Service',
                        'sub_service' => is_string($sub) ? $sub : ($sub['name'] ?? 'General'),
                        'city' => $freelancer->city ?: 'All Operating Cities',
                        'shift' => $freelancer->shift ? ($freelancer->shift === 'both' ? 'Both 12/24 Hr' : $freelancer->shift.' Hr') : '12 Hours',
                        'rate' => $freelancer->expected_salary ? '₹ '.number_format((float) $freelancer->expected_salary, 2) : 'As per RCL',
                        'billed_per' => 'Per shift',
                    ];
                }
            }
        }

        if (empty($rows)) {
            $rows[] = [
                'service' => $freelancer->job_title ?: 'Care Service',
                'sub_service' => 'General Care',
                'city' => $freelancer->city ?: 'All Operating Cities',
                'shift' => $freelancer->shift ? ($freelancer->shift === 'both' ? 'Both 12/24 Hr' : $freelancer->shift.' Hr') : '12 Hours',
                'rate' => $freelancer->expected_salary ? '₹ '.number_format((float) $freelancer->expected_salary, 2) : 'As per RCL',
                'billed_per' => 'Per shift',
            ];
        }

        return $rows;
    }
}
