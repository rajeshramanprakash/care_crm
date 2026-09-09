<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\OperationLead;
use Illuminate\Http\JsonResponse;

class LeadMatchController extends Controller
{
    /**
     * Returns prospect leads (stage closed) that have a matching operation lead by contact number.
     */
    public function prospectMatches(): JsonResponse
    {
        $leads = Lead::query()
            ->where('status', 'prospect')
            ->where('stage', 'closed')
            ->whereNotNull('contact_no')
            ->get([
                'id',
                'customer_name',
                'patient_name',
                'contact_no',
                'executive',
                'status',
                'stage',
                'query',
                'location',
                'lead_source',
                'future_prospect_date',
                'prospect_rate',
                'last_call_status',
                'created_at',
                'updated_at',
            ]);

        if ($leads->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'count' => 0,
                'data' => [],
            ]);
        }

        $numberVariants = [];

        foreach ($leads as $lead) {
            $normalized = $this->normalizePhoneNumber($lead->contact_no);
            if (empty($normalized)) {
                continue;
            }
            $numberVariants = array_merge(
                $numberVariants,
                array_filter([
                    $lead->contact_no,
                    $normalized,
                    '91' . $normalized,
                    '0' . $normalized,
                ])
            );
        }

        $numberVariants = array_values(array_unique($numberVariants));
        $operationLeads = OperationLead::query()
            ->whereIn('contact_no', $numberVariants)
            ->get([
                'id',
                'lead_id',
                'customer_name',
                'patient_name',
                'contact_no',
                'status',
                'shift_type',
                'last_call_status',
                'query',
                'location',
                'executive',
                'vendor_id',
                'created_at',
                'updated_at',
            ]);

        $operationNormalized = [];
        foreach ($operationLeads as $operationLead) {
            $normalized = $this->normalizePhoneNumber($operationLead->contact_no);
            if (empty($normalized)) {
                continue;
            }
            $operationNormalized[$normalized] = true;
        }

        $results = [];
        foreach ($leads as $lead) {
            $normalized = $this->normalizePhoneNumber($lead->contact_no);
            if (empty($normalized)) {
                continue;
            }

            if (!empty($operationNormalized[$normalized] ?? null)) {
                continue;
            }

            $results[] = [
                'id' => $lead->id,
                'formatted_id' => $lead->formatted_id,
                'customer_name' => $lead->customer_name,
                'patient_name' => $lead->patient_name,
                'contact_no' => $lead->contact_no,
                'normalized_contact_no' => $normalized,
                'status' => $lead->status,
                'stage' => $lead->stage,
                'query' => $lead->query,
                'location' => $lead->location,
                'lead_source' => $lead->lead_source,
                'future_prospect_date' => $lead->future_prospect_date,
                'prospect_rate' => $lead->prospect_rate,
                'last_call_status' => $lead->last_call_status,
                'executive_id' => $lead->executive,
                'created_at' => $lead->created_at,
                'updated_at' => $lead->updated_at,
            ];
        }

        return response()->json([
            'status' => 'success',
            'count' => count($results),
            'data' => $results,
        ]);
    }

    private function normalizePhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if (empty($digits)) {
            return null;
        }

        if (strlen($digits) >= 12 && substr($digits, 0, 2) === '91') {
            $digits = substr($digits, 2);
        }

        $digits = ltrim($digits, '0');

        return $digits ?: null;
    }
}

