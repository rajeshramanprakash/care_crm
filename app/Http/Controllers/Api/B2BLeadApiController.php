<?php

namespace App\Http\Controllers\Api;

use App\Exports\B2BCorporateLeadBulkTemplateExport;
use App\Exports\B2BIndividualLeadBulkTemplateExport;
use App\Http\Controllers\Controller;
use App\Facades\UserAssignment;
use App\Imports\B2BCorporateLeadBulkImport;
use App\Imports\B2BIndividualLeadBulkImport;
use App\Models\B2BLead;
use App\Models\B2BUser;
use App\Models\DoctorConsultationService;
use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\OperationDeploymentDetails;
use App\Models\Service;
use App\Models\User;
use App\Services\B2BCorporateChatService;
use App\Services\B2BCorporateLeadService;
use App\Services\B2BIndividualLeadService;
use App\Services\B2BLeadJourneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class B2BLeadApiController extends Controller
{
    public function __construct(
        protected B2BLeadJourneyService $journeyService,
        protected B2BCorporateLeadService $corporateLeadService,
        protected B2BIndividualLeadService $individualLeadService,
        protected B2BCorporateChatService $corporateChatService
    ) {}

    private function b2bInfoOrAbort(Request $request): array
    {
        $user = $request->user();
        $info = Cache::get('b2b_info_' . $user->id);
        abort_if(!$info || empty($info['b2b_user_id']), 403, 'B2B access only.');
        return $info;
    }

    private function b2bUserOrAbort(Request $request): B2BUser
    {
        $info = $this->b2bInfoOrAbort($request);
        $b2bUser = B2BUser::find((int) $info['b2b_user_id']);
        abort_if(!$b2bUser, 403, 'B2B access only.');

        return $b2bUser;
    }

    /** @return array<string, mixed> */
    private function partnerPayload(B2BUser $b2bUser): array
    {
        return [
            'id' => $b2bUser->id,
            'name' => $b2bUser->name,
            'company_name' => $b2bUser->company_name,
            'mobile' => $b2bUser->mobile,
            'account_type' => $b2bUser->account_type,
            'portal_label' => $b2bUser->portalLabel(),
            'is_corporate' => $b2bUser->isCorporate(),
            'is_individual' => $b2bUser->isIndividual(),
            'is_legacy' => $b2bUser->isLegacy(),
            'service_requirement' => $b2bUser->service_requirement,
            'bulk_requirement_qty' => $b2bUser->bulk_requirement_qty,
            'commission_percent' => $b2bUser->commission_percent,
            'chat_enabled' => (bool) $b2bUser->chat_enabled,
            'chat_can_access' => $this->corporateChatService->corporateUserCanChat($b2bUser),
            'chat_group_name' => $b2bUser->chat_group_name,
        ];
    }

    private function b2bReferenceInfoOrAbort(Request $request): array
    {
        $user = $request->user();
        $info = Cache::get('b2b_reference_info_' . $user->id);
        abort_if(! $info || empty($info['b2b_reference_user_id']), 403, 'B2B reference access only.');

        return $info;
    }

    /** @return array<int, int> */
    private function referredB2bUserIds(int $referenceUserId): array
    {
        return B2BUser::query()
            ->where('b2b_reference_user_id', $referenceUserId)
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }

    public function referenceDashboard(Request $request)
    {
        $info = $this->b2bReferenceInfoOrAbort($request);
        $refId = (int) $info['b2b_reference_user_id'];
        $ids = $this->referredB2bUserIds($refId);

        return response()->json([
            'success' => true,
            'total_leads' => B2BLead::whereIn('b2b_user_id', $ids)->count(),
            'today_leads' => B2BLead::whereIn('b2b_user_id', $ids)->whereDate('created_at', now()->toDateString())->count(),
        ]);
    }

    public function referenceLeads(Request $request)
    {
        $info = $this->b2bReferenceInfoOrAbort($request);
        $refId = (int) $info['b2b_reference_user_id'];

        $b2bIds = $this->referredB2bUserIds($refId);
        $items = B2BLead::with(['operationLead', 'lead', 'b2bUser'])
            ->whereIn('b2b_user_id', $b2bIds)
            ->latest()
            ->get();

        $operationLeadIds = $items->pluck('operation_lead_id')->filter()->unique()->values();
        $latestDeployments = OperationDeploymentDetails::query()
            ->whereIn('operation_lead_id', $operationLeadIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('operation_lead_id')
            ->map(fn ($rows) => $rows->first());

        $tableRows = $items->map(function (B2BLead $it) use ($latestDeployments) {
            $b2bUser = $it->b2bUser;
            $lead = $it->lead;
            $op = $it->operationLead;
            if (! $op && $lead) {
                $op = OperationLead::query()
                    ->where(function ($q) use ($lead) {
                        $q->where('lead_id', $lead->formatted_id())
                            ->orWhere('contact_no', $lead->contact_no);
                    })
                    ->orderByDesc('id')
                    ->first();
                if ($op && $it->operation_lead_id !== $op->id) {
                    $it->operation_lead_id = $op->id;
                    $it->save();
                }
            }
            $deployment = $latestDeployments->get($it->operation_lead_id);
            $status = (string) ($lead?->status ?? $op?->status ?? '—');
            $remark = '';
            if (strtolower($status) === 'inactive') {
                $remark = (string) ($lead?->inactive_stage_remark ?: $op?->inactive_remark ?: $op?->status_remark ?: '');
            } elseif (strtolower($status) === 'closed') {
                $remark = (string) ($op?->closed_remark ?: $lead?->status_remarks ?: $op?->status_remark ?: '');
            } else {
                $remark = (string) ($lead?->status_remarks ?: $op?->status_remark ?: '');
            }
            $monthlyAmount = null;
            if (! empty($op?->closed_rate)) {
                $monthlyAmount = (float) $op->closed_rate;
            } elseif (! empty($deployment?->vendor_payment)) {
                $monthlyAmount = (float) $deployment->vendor_payment;
            }
            $commissionPercent = (float) ($b2bUser?->commission_percent ?? 0);
            $monthlyEarning = $monthlyAmount !== null ? round(($monthlyAmount * $commissionPercent) / 100, 2) : null;

            return [
                'id' => $it->id,
                'b2b_partner' => (string) ($b2bUser?->company_name ?: $b2bUser?->name ?: '—'),
                'lead_no' => (string) (($lead?->formatted_id()) ?? ($op?->lead_id) ?? ('B2B-' . $it->id)),
                'client_name' => (string) ($lead?->customer_name ?? $op?->customer_name ?? $it->name),
                'service' => (string) ($lead?->query ?? $op?->query ?? $it->service_requirement),
                'city' => (string) ($lead?->location ?? $op?->location ?? '—'),
                'price_date' => ($monthlyAmount !== null ? number_format($monthlyAmount, 2) : '—') . ' / ' . (optional($lead?->created_at ?? $op?->date_time ?? $it->created_at)->format('d M Y') ?? '—'),
                'status_with_remark' => trim($status . ($remark !== '' ? (' (' . $remark . ')') : '')),
                'from_to' => strtolower($status) === 'closed'
                    ? ((optional($deployment?->deployment_from_date)->format('d M Y') ?? '—') . ' - ' . (optional($deployment?->deployment_to_date)->format('d M Y') ?? '—'))
                    : '—',
                'total_amount_monthly' => $monthlyAmount !== null ? number_format($monthlyAmount, 2) : '—',
                'total_earnings_monthly' => $monthlyEarning !== null ? number_format($monthlyEarning, 2) : '—',
                'source' => (string) ($lead?->lead_source ?? $it->source ?? 'manual'),
            ];
        })->values();

        return response()->json(['success' => true, 'items' => $items, 'table_rows' => $tableRows]);
    }

    public function dashboard(Request $request)
    {
        $b2bUser = $this->b2bUserOrAbort($request);

        return response()->json([
            'success' => true,
            'total_leads' => B2BLead::where('b2b_user_id', $b2bUser->id)->count(),
            'today_leads' => B2BLead::where('b2b_user_id', $b2bUser->id)->whereDate('created_at', now()->toDateString())->count(),
            'partner' => $this->partnerPayload($b2bUser),
        ]);
    }

    public function serviceOptions(Request $request)
    {
        $this->b2bInfoOrAbort($request);
        $regular = Service::query()->select('name')->orderBy('name')->pluck('name')->toArray();
        $doctor = DoctorConsultationService::query()->select('name')->orderBy('name')->pluck('name')->toArray();
        $all = array_values(array_unique(array_filter(array_merge($regular, $doctor))));
        natcasesort($all);

        return response()->json(['success' => true, 'services' => array_values($all)]);
    }

    public function index(Request $request)
    {
        $b2bUser = $this->b2bUserOrAbort($request);

        $items = B2BLead::with(['operationLead', 'lead'])
            ->where('b2b_user_id', $b2bUser->id)
            ->latest()
            ->get();

        $operationLeadIds = $items->pluck('operation_lead_id')->filter()->unique()->values();
        $latestDeployments = OperationDeploymentDetails::query()
            ->whereIn('operation_lead_id', $operationLeadIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('operation_lead_id')
            ->map(function ($rows) {
                return $rows->first();
            });

        $tableRows = $items->map(function (B2BLead $it) use ($b2bUser, $latestDeployments) {
            $lead = $it->lead;
            $op = $it->operationLead;
            if (!$op && $lead) {
                $op = OperationLead::query()
                    ->where(function ($q) use ($lead) {
                        $q->where('lead_id', $lead->formatted_id())
                            ->orWhere('contact_no', $lead->contact_no);
                    })
                    ->orderByDesc('id')
                    ->first();
                if ($op && $it->operation_lead_id !== $op->id) {
                    $it->operation_lead_id = $op->id;
                    $it->save();
                }
            }
            $deployment = $latestDeployments->get($it->operation_lead_id);
            $status = (string) ($lead?->status ?? $op?->status ?? '—');
            $remark = '';
            if (strtolower($status) === 'inactive') {
                $remark = (string) ($lead?->inactive_stage_remark ?: $op?->inactive_remark ?: $op?->status_remark ?: '');
            } elseif (strtolower($status) === 'closed') {
                $remark = (string) ($op?->closed_remark ?: $lead?->status_remarks ?: $op?->status_remark ?: '');
            } else {
                $remark = (string) ($lead?->status_remarks ?: $op?->status_remark ?: '');
            }
            $monthlyAmount = null;
            if (!empty($op?->closed_rate)) {
                $monthlyAmount = (float) $op->closed_rate;
            } elseif (!empty($deployment?->vendor_payment)) {
                $monthlyAmount = (float) $deployment->vendor_payment;
            }
            $commissionPercent = (float) ($b2bUser->commission_percent ?? 0);
            $monthlyEarning = $monthlyAmount !== null ? round(($monthlyAmount * $commissionPercent) / 100, 2) : null;

            return [
                'id' => $it->id,
                'lead_no' => (string) (($lead?->formatted_id()) ?? $op->lead_id ?? ('B2B-' . $it->id)),
                'client_name' => (string) ($lead?->customer_name ?? $op->customer_name ?? $it->name ?? $b2bUser->company_name ?? '—'),
                'service' => (string) ($lead?->query ?? $op->query ?? $it->service_requirement),
                'bulk_qty' => $it->bulk_qty,
                'detail' => $it->detail,
                'city' => (string) ($lead?->location ?? $op->location ?? '—'),
                'price_date' => ($monthlyAmount !== null ? number_format($monthlyAmount, 2) : '—') . ' / ' . (optional($lead?->created_at ?? $op?->date_time ?? $it->created_at)->format('d M Y') ?? '—'),
                'status_with_remark' => trim($status . ($remark !== '' ? (' (' . $remark . ')') : '')),
                'from_to' => strtolower($status) === 'closed'
                    ? ((optional($deployment?->deployment_from_date)->format('d M Y') ?? '—') . ' - ' . (optional($deployment?->deployment_to_date)->format('d M Y') ?? '—'))
                    : '—',
                'total_amount_monthly' => $monthlyAmount !== null ? number_format($monthlyAmount, 2) : '—',
                'total_earnings_monthly' => $monthlyEarning !== null ? number_format($monthlyEarning, 2) : '—',
                'source' => (string) ($lead?->lead_source ?? $it->source ?? 'manual'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'items' => $items,
            'table_rows' => $tableRows,
            'partner' => $this->partnerPayload($b2bUser),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $b2bUser = $this->b2bUserOrAbort($request);
        $b2bLead = B2BLead::query()
            ->where('b2b_user_id', $b2bUser->id)
            ->whereKey($id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'detail' => $this->journeyService->buildDetail($b2bLead),
        ]);
    }

    public function store(Request $request)
    {
        $b2bUser = $this->b2bUserOrAbort($request);

        if ($b2bUser->isCorporate()) {
            $item = $this->corporateLeadService->createLead($b2bUser, $request->only([
                'number', 'service', 'detail', 'bulk',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Lead submitted to operations successfully.',
                'item' => $item,
            ]);
        }

        if ($b2bUser->isIndividual()) {
            $item = $this->individualLeadService->createLead($b2bUser, $request->only([
                'number', 'service', 'detail', 'bulk',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Lead submitted to sales successfully.',
                'item' => $item,
            ]);
        }

        $info = $this->b2bInfoOrAbort($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'regex:/^[0-9]{10}$/'],
            'service_requirement' => ['required', 'string', 'max:255'],
        ]);

        $item = B2BLead::create([
            'b2b_user_id' => $b2bUser->id,
            'lead_id' => $this->createSalesLead(
                $data['name'],
                $data['mobile'],
                $data['service_requirement'],
                $this->buildB2BLeadSource($info)
            ),
            'operation_lead_id' => null,
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'service_requirement' => $data['service_requirement'],
            'source' => 'manual',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully.',
            'item' => $item,
        ]);
    }

    public function import(Request $request)
    {
        $b2bUser = $this->b2bUserOrAbort($request);

        if ($b2bUser->isCorporate()) {
            $request->validate([
                'bulk_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            ]);

            try {
                $import = new B2BCorporateLeadBulkImport($b2bUser, $this->corporateLeadService);
                Excel::import($import, $request->file('bulk_file'));
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'success' => false,
                    'message' => 'Bulk upload failed: '.$e->getMessage(),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'inserted' => $import->getInsertedCount(),
                'errors' => $import->getErrors(),
                'message' => $import->getInsertedCount().' lead(s) submitted to operations.',
            ]);
        }

        if ($b2bUser->isIndividual()) {
            $request->validate([
                'bulk_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            ]);

            try {
                $import = new B2BIndividualLeadBulkImport($b2bUser, $this->individualLeadService);
                Excel::import($import, $request->file('bulk_file'));
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'success' => false,
                    'message' => 'Bulk upload failed: '.$e->getMessage(),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'inserted' => $import->getInsertedCount(),
                'errors' => $import->getErrors(),
                'message' => $import->getInsertedCount().' lead(s) submitted to sales.',
            ]);
        }

        $info = $this->b2bInfoOrAbort($request);
        $request->validate([
            'bulk_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $rows = [];
        if (($handle = fopen($request->file('bulk_file')->getRealPath(), 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (!$header || count($header) !== count($row)) {
                    continue;
                }
                $rows[] = array_combine($header, $row);
            }
            fclose($handle);
        }

        $inserted = 0;
        foreach ($rows as $r) {
            $name = trim((string) ($r['name'] ?? ''));
            $mobile = preg_replace('/\D+/', '', (string) ($r['mobile'] ?? ''));
            $service = trim((string) ($r['service_requirement'] ?? ''));
            if ($name === '' || strlen($mobile) !== 10 || $service === '') {
                continue;
            }
            B2BLead::create([
                'b2b_user_id' => $b2bUser->id,
                'lead_id' => $this->createSalesLead(
                    $name,
                    $mobile,
                    $service,
                    $this->buildB2BLeadSource($info)
                ),
                'operation_lead_id' => null,
                'name' => $name,
                'mobile' => $mobile,
                'service_requirement' => $service,
                'source' => 'bulk',
            ]);
            $inserted++;
        }

        return response()->json([
            'success' => true,
            'inserted' => $inserted,
            'message' => $inserted.' leads imported successfully.',
        ]);
    }

    public function downloadTemplate(Request $request): StreamedResponse|\Illuminate\Http\Response
    {
        $b2bUser = $this->b2bUserOrAbort($request);

        if ($b2bUser->isCorporate()) {
            return Excel::download(
                new B2BCorporateLeadBulkTemplateExport(),
                'b2b_corporate_leads_template.xlsx'
            );
        }

        if ($b2bUser->isIndividual()) {
            return Excel::download(
                new B2BIndividualLeadBulkTemplateExport(),
                'b2b_individual_leads_template.xlsx'
            );
        }

        $csv = "name,mobile,service_requirement\n";
        $csv .= "Rahul Sharma,9876543210,Doctor consultation\n";
        $csv .= "Anita Verma,9123456789,Nursing Care\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="b2b_leads_template.csv"',
        ]);
    }

    private function createSalesLead(string $name, string $mobile, string $serviceRequirement, string $leadSource): ?int
    {
        try {
            $assignedExecutive = UserAssignment::getAssigningUser(2, null, 'web');
            if (!$assignedExecutive) {
                $assignedExecutive = User::query()
                    ->whereRaw('FIND_IN_SET(role_id, "2")')
                    ->orderBy('id')
                    ->first();
            }

            $lead = Lead::create([
                'date' => now(),
                'executive' => $assignedExecutive?->id,
                'customer_name' => $name,
                'contact_no' => $mobile,
                'query' => $serviceRequirement,
                'query_remarks' => 'B2B lead',
                'lead_source' => $leadSource,
                'contact_type' => 'call',
                'status' => 'follow-up',
                'stage' => 'active',
            ]);
            return $lead->id;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildB2BLeadSource(array $info): string
    {
        $company = trim((string) ($info['company_name'] ?? ''));
        if ($company === '') {
            $b2b = B2BUser::find((int) ($info['b2b_user_id'] ?? 0));
            $company = trim((string) ($b2b->company_name ?? $b2b->name ?? ''));
        }
        return $company !== '' ? ('b2b (' . $company . ')') : 'b2b';
    }
}

