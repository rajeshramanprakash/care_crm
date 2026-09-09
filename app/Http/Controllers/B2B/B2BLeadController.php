<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\B2B\Concerns\ResolvesB2BPartnerPortal;
use App\Http\Controllers\Controller;
use App\Facades\UserAssignment;
use App\Models\B2BLead;
use App\Exports\B2BCorporateLeadBulkTemplateExport;
use App\Exports\B2BIndividualLeadBulkTemplateExport;
use App\Imports\B2BCorporateLeadBulkImport;
use App\Imports\B2BIndividualLeadBulkImport;
use App\Services\B2BCorporateLeadService;
use App\Services\B2BIndividualLeadService;
use App\Services\B2BLeadJourneyService;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\B2BUser;
use App\Models\DoctorConsultationService;
use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\OperationDeploymentDetails;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class B2BLeadController extends Controller
{
    use ResolvesB2BPartnerPortal;

    public function __construct(
        protected B2BLeadJourneyService $journeyService,
        protected B2BCorporateLeadService $corporateLeadService,
        protected B2BIndividualLeadService $individualLeadService
    ) {}

    public function dashboard()
    {
        $b2bUser = $this->currentB2BUser();
        if (! $b2bUser) {
            return redirect()->route('home')->with('error', 'Session expired. Please login again.');
        }

        $totalLeads = B2BLead::where('b2b_user_id', $b2bUser->id)->count();
        $todayLeads = B2BLead::where('b2b_user_id', $b2bUser->id)->whereDate('created_at', now()->toDateString())->count();

        return view($this->portalDashboardView($b2bUser), array_merge(
            $this->portalContext($b2bUser),
            compact('b2bUser', 'totalLeads', 'todayLeads')
        ));
    }

    public function index()
    {
        $b2bUser = $this->currentB2BUser();
        if (! $b2bUser) {
            return redirect()->route('home')->with('error', 'Session expired. Please login again.');
        }

        $items = B2BLead::with(['operationLead', 'lead'])
            ->where('b2b_user_id', $b2bUser->id)
            ->latest()
            ->get();
        $serviceOptions = $this->serviceOptions();
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
        });

        $isCorporatePortal = $b2bUser->isCorporate();
        $isIndividualPortal = $b2bUser->isIndividual();

        return view('b2b.leads.index', array_merge(
            $this->portalContext($b2bUser),
            compact('b2bUser', 'items', 'serviceOptions', 'tableRows', 'isCorporatePortal', 'isIndividualPortal')
        ));
    }

    public function show(int $id)
    {
        $b2bUser = $this->currentB2BUser();
        if (! $b2bUser) {
            return response()->json(['message' => 'Session expired.'], 401);
        }

        $b2bLead = B2BLead::query()
            ->where('b2b_user_id', $b2bUser->id)
            ->whereKey($id)
            ->firstOrFail();

        return response()->json($this->journeyService->buildDetail($b2bLead));
    }

    public function store(Request $request)
    {
        $b2bUser = $this->currentB2BUser();
        if (! $b2bUser) {
            return redirect()->route('home')->with('error', 'Session expired. Please login again.');
        }

        if ($b2bUser->isCorporate()) {
            $this->corporateLeadService->createLead($b2bUser, $request->only([
                'number', 'service', 'detail', 'bulk',
            ]));

            return redirect()->route($this->portalRoutePrefix($b2bUser).'.leads.index')
                ->with('status', 'Lead submitted to operations successfully.');
        }

        if ($b2bUser->isIndividual()) {
            $this->individualLeadService->createLead($b2bUser, $request->only([
                'number', 'service', 'detail', 'bulk',
            ]));

            return redirect()->route($this->portalRoutePrefix($b2bUser).'.leads.index')
                ->with('status', 'Lead submitted to sales successfully.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'regex:/^[0-9]{10}$/'],
            'service_requirement' => ['required', 'string', 'max:255'],
        ]);

        B2BLead::create([
            'b2b_user_id' => $b2bUser->id,
            'lead_id' => $this->createSalesLead(
                $data['name'],
                $data['mobile'],
                $data['service_requirement'],
                $this->buildB2BLeadSource($b2bUser)
            ),
            'operation_lead_id' => null,
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'service_requirement' => $data['service_requirement'],
            'source' => 'manual',
        ]);

        return redirect()->route($this->portalRoutePrefix($b2bUser).'.leads.index')->with('status', 'Lead created successfully.');
    }

    public function import(Request $request)
    {
        $b2bUser = $this->currentB2BUser();
        if (! $b2bUser) {
            return redirect()->route('home')->with('error', 'Session expired. Please login again.');
        }

        if ($b2bUser->isCorporate()) {
            $request->validate([
                'bulk_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            ]);

            try {
                $import = new B2BCorporateLeadBulkImport($b2bUser, $this->corporateLeadService);
                Excel::import($import, $request->file('bulk_file'));
            } catch (\Throwable $e) {
                report($e);

                return redirect()->route($this->portalRoutePrefix($b2bUser).'.leads.index')
                    ->with('error', 'Bulk upload failed: '.$e->getMessage());
            }

            $inserted = $import->getInsertedCount();
            $errors = $import->getErrors();
            $message = $inserted.' lead(s) submitted to operations.';
            if ($import->getErrorCount() > 0) {
                $message .= ' '.$import->getErrorCount().' row(s) had errors.';
            }

            return redirect()->route($this->portalRoutePrefix($b2bUser).'.leads.index')
                ->with('status', $message)
                ->with('import_errors', $errors);
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

                return redirect()->route($this->portalRoutePrefix($b2bUser).'.leads.index')
                    ->with('error', 'Bulk upload failed: '.$e->getMessage());
            }

            $inserted = $import->getInsertedCount();
            $errors = $import->getErrors();
            $message = $inserted.' lead(s) submitted to sales.';
            if ($import->getErrorCount() > 0) {
                $message .= ' '.$import->getErrorCount().' row(s) had errors.';
            }

            return redirect()->route($this->portalRoutePrefix($b2bUser).'.leads.index')
                ->with('status', $message)
                ->with('import_errors', $errors);
        }

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
                    $this->buildB2BLeadSource($b2bUser)
                ),
                'operation_lead_id' => null,
                'name' => $name,
                'mobile' => $mobile,
                'service_requirement' => $service,
                'source' => 'bulk',
            ]);
            $inserted++;
        }

        return redirect()->route($this->portalRoutePrefix($b2bUser) . '.leads.index')->with('status', $inserted . ' leads imported successfully.');
    }

    public function downloadTemplate(Request $request)
    {
        $b2bUser = $this->currentB2BUser();
        if ($b2bUser?->isCorporate()) {
            return Excel::download(
                new B2BCorporateLeadBulkTemplateExport(),
                'b2b_corporate_leads_template.xlsx'
            );
        }

        if ($b2bUser?->isIndividual()) {
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
            'Content-Disposition' => 'attachment; filename="b2b_leads_format.csv"',
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

    private function buildB2BLeadSource(B2BUser $b2bUser): string
    {
        $company = trim((string) ($b2bUser->company_name ?? $b2bUser->name ?? ''));
        return $company !== '' ? ('b2b (' . $company . ')') : 'b2b';
    }

    private function serviceOptions(): array
    {
        $regular = Service::query()->select('name')->orderBy('name')->pluck('name')->toArray();
        $doctor = DoctorConsultationService::query()->select('name')->orderBy('name')->pluck('name')->toArray();
        $all = array_values(array_unique(array_filter(array_merge($regular, $doctor))));
        natcasesort($all);
        return array_values($all);
    }
}

