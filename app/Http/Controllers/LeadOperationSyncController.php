<?php

namespace App\Http\Controllers;

use App\Facades\UserAssignment;
use App\Models\Lead;
use App\Models\Location;
use App\Models\OperationLead;
use App\Models\WhatsappMsgGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LeadOperationSyncController extends Controller
{
    /**
     * Create missing OperationLead records for prospect + closed leads.
     */
    public function createFromProspects(): JsonResponse
    {
        $leads = Lead::query()
            ->where('status', 'prospect')
            ->where('stage', 'closed')
            ->whereNotNull('contact_no')
            ->get();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($leads as $lead) {
            $normalized = $this->normalizePhoneNumber($lead->contact_no);
            if (empty($normalized)) {
                $skipped++;
                continue;
            }

            $operationLead = OperationLead::query()
                ->whereIn('contact_no', array_unique(array_filter([
                    $lead->contact_no,
                    $normalized,
                    '91' . $normalized,
                    '0' . $normalized,
                ])))
                ->first();

            try {
                if (!$operationLead) {
                    $operationLead = new OperationLead();
                    $this->populateOperationLead($operationLead, $lead);
                    $operationLead->date_time = now();
                    $operationLead->status = 'follow-up';
                    $operationLead->save();
                    $this->syncWhatsappGroup($lead->contact_no, $operationLead->executive);
                    $created++;

                    Log::info('LeadOperationSync: Created OperationLead from prospect lead', [
                        'lead_id' => $lead->id,
                        'operation_lead_id' => $operationLead->id,
                        'contact_no' => $lead->contact_no,
                    ]);
                } else {
                    $operationLead->date_time = now();
                    if (empty($operationLead->lead_id)) {
                        $operationLead->lead_id = $lead->formatted_id;
                    }
                    $operationLead->last_call_status = $operationLead->last_call_status ?? $lead->last_call_status;
                    $operationLead->save();
                    $updated++;

                    Log::info('LeadOperationSync: Updated existing OperationLead timestamp', [
                        'lead_id' => $lead->id,
                        'operation_lead_id' => $operationLead->id,
                        'contact_no' => $lead->contact_no,
                    ]);
                }
            } catch (\Throwable $th) {
                $errors[] = [
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no,
                    'error' => $th->getMessage(),
                ];
                Log::error('LeadOperationSync: Failed to sync lead to operation lead', [
                    'lead_id' => $lead->id,
                    'contact_no' => $lead->contact_no,
                    'exception' => $th,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'summary' => [
                'total_leads_considered' => $leads->count(),
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => count($errors),
            ],
            'errors' => $errors,
        ]);
    }

    private function populateOperationLead(OperationLead $operationLead, Lead $lead): void
    {
        $location = Location::where('name', $lead->location)->first();
        $assignedExecutive = null;

        try {
            $assignedExecutive = UserAssignment::getAssigningUser(4, $location->id ?? null);
        } catch (\Throwable $th) {
            Log::warning('LeadOperationSync: Unable to assign executive from UserAssignment', [
                'lead_id' => $lead->id,
                'contact_no' => $lead->contact_no,
                'exception' => $th->getMessage(),
            ]);
        }

        if ($assignedExecutive) {
            $operationLead->executive = $assignedExecutive->id;
        }

        $operationLead->customer_name = $lead->customer_name;
        $operationLead->contact_type = $lead->contact_type;
        $operationLead->contact_no = $lead->contact_no;
        $operationLead->address = $lead->address ?? null;
        $operationLead->location = $lead->location;
        $operationLead->query = $lead->query;
        $operationLead->status = $operationLead->status ?? 'follow-up';
        $operationLead->closed_rate = $lead->prospect_rate;
        $operationLead->lead_id = $lead->formatted_id;
        $operationLead->patient_gender = $lead->patient_gender;
        $operationLead->patient_name = $lead->patient_name;
        $operationLead->shift_type = $lead->shift_type;
        $operationLead->age = $lead->age;
    }

    private function syncWhatsappGroup(string $number, ?int $executiveId): void
    {
        if (!$executiveId) {
            return;
        }

        $group = WhatsappMsgGroup::where('whatsapp_number', $number)->first();

        if ($group) {
            $ids = array_filter(explode(',', $group->executive_ids));
            if (!in_array($executiveId, $ids)) {
                $ids[] = $executiveId;
                $group->executive_ids = implode(',', $ids);
                $group->save();
            }
        } else {
            WhatsappMsgGroup::create([
                'whatsapp_number' => $number,
                'executive_ids' => $executiveId,
            ]);
        }
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

