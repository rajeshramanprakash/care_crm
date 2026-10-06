<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeLeadWithAi;
use App\Models\Lead;
use App\Models\LeadAiAnalysis;
use App\Models\OperationLead;
use App\Support\LeadAi\LeadAiAccess;
use App\Support\LeadAi\LeadAiAutoTrigger;
use App\Support\LeadAi\LeadAnalyzer;
use Illuminate\Http\Request;

/**
 * AI lead review for Admin, Sales Manager (own team's sales leads) and Operation Manager (own team's operation leads).
 * Mounted under admin.*, manager.* and operation-manager.*.
 */
class LeadAiInsightController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(LeadAiAccess::role() === LeadAiAccess::ADMIN, 403);
        $types = LeadAiAccess::types();
        $type = in_array($request->query('type'), $types, true) ? $request->query('type') : $types[0];
        $user = $request->user();

        $leadTable = $type === 'operation' ? 'operation_leads' : 'leads';
        $query = LeadAiAnalysis::query()
            ->where('lead_ai_analyses.lead_type', $type)
            ->join($leadTable.' as l', 'l.id', '=', 'lead_ai_analyses.lead_id')
            ->leftJoin('users as u', 'u.id', '=', 'l.executive')
            ->select('lead_ai_analyses.*', 'l.customer_name', 'l.contact_no', 'l.status as lead_status', 'l.query as service', 'u.f_name as executive_name')
            ->when(LeadAiAccess::role() !== LeadAiAccess::ADMIN, fn ($q) => LeadAiAccess::scopeTeam($q, $user, 'l.executive'))
            ->when(array_key_exists((string) $request->query('health'), LeadAiAnalysis::HEALTH), fn ($q) => $q->where('lead_ai_analyses.health', $request->query('health')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->query('q').'%';
                $q->where(fn ($w) => $w->where('l.customer_name', 'like', $term)->orWhere('l.contact_no', 'like', $term)
                    ->when(ctype_digit((string) $request->query('q')), fn ($x) => $x->orWhere('l.id', (int) $request->query('q'))));
            });

        $healthCounts = (clone $query)->reorder()->getQuery()->cloneWithout(['columns', 'orders'])
            ->selectRaw('lead_ai_analyses.health, count(*) c')->groupBy('lead_ai_analyses.health')->pluck('c', 'health');

        return view('lead_ai.index', [
            'layout' => LeadAiAccess::layout(),
            'rp' => LeadAiAccess::routePrefix(),
            'types' => $types,
            'type' => $type,
            'analyses' => $query->orderByDesc('lead_ai_analyses.analyzed_at')->orderByDesc('lead_ai_analyses.id')->paginate(20)->withQueryString(),
            'healthCounts' => $healthCounts,
            'configured' => LeadAnalyzer::configured(),
        ]);
    }

    public function status(Request $request, string $type, int $id)
    {
        $this->authorizeLead($request, $type, $id);
        $analysis = LeadAiAnalysis::where('lead_type', $type)->where('lead_id', $id)->first();

        if (! $analysis?->isRunning() && LeadAiAutoTrigger::enabled() && AnalyzeLeadWithAi::claimAutoRun($type, $id, false)) {
            $analysis ??= new LeadAiAnalysis(['lead_type' => $type, 'lead_id' => $id]);
            $analysis->fill(['status' => 'pending', 'started_at' => now(), 'error' => null])->save();
            AnalyzeLeadWithAi::dispatchAfterResponse($type, $id, (int) $request->user()->id);
        }

        return response()->json([
            'status' => $analysis?->status ?? 'none',
            'running' => (bool) $analysis?->isRunning(),
            'html' => view('lead_ai._result', ['analysis' => $analysis, 'type' => $type, 'configured' => LeadAnalyzer::configured()])->render(),
        ]);
    }

    public function analyze(Request $request, string $type, int $id)
    {
        $this->authorizeLead($request, $type, $id);
        if (! LeadAiAccess::canReanalyze()) {
            return response()->json(['ok' => false, 'message' => 'Only Admin can re-analyze a lead.'], 403);
        }
        if (! LeadAnalyzer::configured()) {
            return response()->json(['ok' => false, 'message' => 'AI is not configured on this server (GEMINI_API_KEY missing).'], 422);
        }

        $analysis = LeadAiAnalysis::firstOrNew(['lead_type' => $type, 'lead_id' => $id]);
        if (! $analysis->isRunning()) {
            $analysis->fill(['status' => 'pending', 'started_at' => now(), 'error' => null, 'requested_by' => $request->user()->id])->save();
            AnalyzeLeadWithAi::dispatchAfterResponse($type, $id, (int) $request->user()->id);
        }

        return response()->json(['ok' => true, 'status' => $analysis->status]);
    }

    public function show(Request $request, string $type, int $id)
    {
        abort_unless(LeadAiAccess::role() === LeadAiAccess::ADMIN, 403);
        $this->authorizeLead($request, $type, $id);
        $lead = $type === 'operation' ? OperationLead::withTrashed()->findOrFail($id) : Lead::findOrFail($id);
        $rp = LeadAiAccess::routePrefix();
        $leadUrl = $type === 'operation' ? route('admin.operation_leads.show', $id) : route('admin.leads.show', $id);

        return view('lead_ai.show', [
            'layout' => LeadAiAccess::layout(),
            'rp' => $rp,
            'type' => $type,
            'lead' => $lead,
            'leadUrl' => $leadUrl,
        ]);
    }

    private function authorizeLead(Request $request, string $type, int $id): void
    {
        abort_unless(LeadAiAccess::canView($request->user(), $type, $id), 404);
    }
}
