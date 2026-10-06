<?php

namespace App\Support;

use App\Models\CustomerFeedback;
use App\Models\OperationDeploymentDetails;
use App\Models\OperationLead;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * The logged-in customer (session based, see AuthController customer login).
 * Feedback and support tickets are keyed by the 10-digit contact number so a customer sees the same
 * records whether they logged in as a lead, an operation lead or a consultation booking.
 */
class CustomerPortal
{
    public const FINISHED_DEPLOYMENT_STATUSES = ['Completed', 'Stop'];

    /**
     * @return array{id: mixed, type: string, contact_no: string, raw_contact_no: string, name: string}|null
     */
    public static function current(): ?array
    {
        $id = Session::get('customer_id');
        $rawContact = (string) Session::get('contact_no', '');
        $contact = static::normalizeContact($rawContact);

        if (! $id || $contact === '') {
            return null;
        }

        return [
            'id' => $id,
            'type' => (string) Session::get('customer_type', 'lead'),
            'contact_no' => $contact,
            'raw_contact_no' => $rawContact,
            'name' => (string) Session::get('customer_name', 'Customer'),
        ];
    }

    public static function normalizeContact(?string $contact): string
    {
        $digits = preg_replace('/\D+/', '', (string) $contact);

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    /**
     * Completed services of this customer that have no feedback yet (newest first).
     */
    public static function servicesAwaitingFeedback(array $customer): Collection
    {
        $mobile = $customer['contact_no'];
        if (strlen($mobile) !== 10) {
            return new Collection();
        }

        return OperationLead::query()
            ->where(fn ($q) => $q->whereIn('contact_no', array_values(array_unique(array_filter([$customer['raw_contact_no'], $mobile, '91' . $mobile, '+91' . $mobile]))))
                ->orWhereRaw(
                    "RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(contact_no,''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), 10) = ?",
                    [$mobile]
                ))
            ->whereIn('id', OperationDeploymentDetails::query()
                ->whereIn('deployment_status', self::FINISHED_DEPLOYMENT_STATUSES)
                ->select('operation_lead_id'))
            ->whereNotIn('id', CustomerFeedback::query()->whereNotNull('operation_lead_id')->select('operation_lead_id'))
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();
    }

    /**
     * Who the feedback belongs to: the finished deployment (freelancer / vendor) of the service, the Operation
     * user handling the lead (operation_leads.executive) and that user's Operation Manager (users.parent_id).
     * Stored on the feedback so it stays with them even if the lead is reassigned later.
     */
    public static function feedbackRouting(OperationLead $lead): array
    {
        $deployment = OperationDeploymentDetails::query()
            ->where('operation_lead_id', $lead->id)
            ->whereIn('deployment_status', self::FINISHED_DEPLOYMENT_STATUSES)
            ->orderByDesc('id')
            ->first();

        $operationUser = $lead->executive ? User::withTrashed()->find((int) $lead->executive) : null;

        return [
            'deployment_id' => $deployment?->id,
            'staff_name' => $deployment?->staff_name,
            'vendor_id' => $deployment?->vendor_id ?: null,
            'freelancer_id' => $deployment?->freelance_staff_id ?: null,
            'operation_user_id' => $operationUser?->id,
            'operation_manager_id' => $operationUser?->parent_id ?: null,
        ];
    }
}
