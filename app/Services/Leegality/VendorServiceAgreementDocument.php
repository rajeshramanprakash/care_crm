<?php

namespace App\Services\Leegality;

use App\Models\Vendor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class VendorServiceAgreementDocument
{
    /**
     * @return array<string, mixed>
     */
    public function viewData(Vendor $vendor, ?string $irn = null): array
    {
        $irn = $irn ?: ('VEN-'.$vendor->id.'-'.now()->format('YmdHis'));
        $scheduleRows = $this->buildScheduleRows($vendor);

        return [
            'vendor' => $vendor,
            'irn' => $irn,
            'generatedAt' => now(),
            'agreementDate' => now()->format('d M Y'),
            'agreement_number' => $vendor->agreement_number ?? ('CLX-AGR-VEN-'.now()->format('Y').'-'.str_pad($vendor->id, 6, '0', STR_PAD_LEFT)),
            'partner_id' => $vendor->lead_id ?? ('CLX-VEN-'.str_pad($vendor->id, 6, '0', STR_PAD_LEFT)),
            'scheduleRows' => $scheduleRows,
        ];
    }

    public function renderHtml(Vendor $vendor, ?string $irn = null): string
    {
        return view('pdf.vendor-service-agreement', $this->viewData($vendor, $irn))->render();
    }

    public function makePdf(Vendor $vendor, ?string $irn = null)
    {
        $html = $this->renderHtml($vendor, $irn);

        return Pdf::loadHTML($html)->setPaper('a4');
    }

    public function pdfBinary(Vendor $vendor, ?string $irn = null): string
    {
        return $this->makePdf($vendor, $irn)->output();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildScheduleRows(Vendor $vendor): array
    {
        $rows = [];
        $cityShifts = is_array($vendor->service_city_shifts) ? $vendor->service_city_shifts : [];
        $vendorServices = is_array($vendor->vendor_services) ? $vendor->vendor_services : [];

        if (! empty($cityShifts)) {
            foreach ($cityShifts as $cs) {
                if (! is_array($cs)) {
                    continue;
                }
                $rows[] = [
                    'service' => $cs['service'] ?? ($vendor->job_title ?: 'Staff Supply'),
                    'sub_service' => $cs['sub_service'] ?? 'General',
                    'city' => $cs['city'] ?? ($vendor->location ?: 'Operating Cities'),
                    'rate_12hr' => ! empty($cs['rate_12hr']) ? '₹ '.number_format((float) $cs['rate_12hr'], 2) : 'As per RCL',
                    'rate_24hr' => ! empty($cs['rate_24hr']) ? '₹ '.number_format((float) $cs['rate_24hr'], 2) : 'As per RCL',
                    'rate_visit' => ! empty($cs['rate_visit']) ? '₹ '.number_format((float) $cs['rate_visit'], 2) : 'As per RCL',
                ];
            }
        }

        if (empty($rows) && ! empty($vendorServices)) {
            foreach ($vendorServices as $vs) {
                $rows[] = [
                    'service' => is_string($vs) ? $vs : ($vs['name'] ?? 'Staff Supply'),
                    'sub_service' => 'All Sub-services',
                    'city' => $vendor->location ?: 'Operating Cities',
                    'rate_12hr' => 'As per RCL',
                    'rate_24hr' => 'As per RCL',
                    'rate_visit' => 'As per RCL',
                ];
            }
        }

        if (empty($rows)) {
            $rows[] = [
                'service' => $vendor->job_title ?: 'Staff Supply Care Services',
                'sub_service' => 'General Supply',
                'city' => $vendor->location ?: 'Operating Cities',
                'rate_12hr' => 'As per RCL',
                'rate_24hr' => 'As per RCL',
                'rate_visit' => 'As per RCL',
            ];
        }

        return $rows;
    }
}
