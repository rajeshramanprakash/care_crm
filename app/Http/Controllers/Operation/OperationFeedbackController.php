<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Controller;
use App\Models\CustomerFeedback;
use Illuminate\Http\Request;

/**
 * Customer feedback for the Operation user who handled the service and their Operation Manager (read only).
 * Mounted under operation.* and operation-manager.*.
 */
class OperationFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $isManager = $this->isManager($request);
        $tab = $request->query('tab', 'all');
        $base = CustomerFeedback::query()->visibleToOperation($request->user(), $isManager);

        $feedbacks = (clone $base)
            ->with(['operationUser:id,f_name,l_name', 'operationLead:id,query,patient_name'])
            ->when($tab === 'complaints', fn ($q) => $q->whereNotNull('complaint')->where('complaint', '!=', ''))
            ->when($tab === 'low', fn ($q) => $q->where('overall_rating', '<=', 2))
            ->when($tab === 'unseen', fn ($q) => $q->whereNull($this->seenColumn($isManager)))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->query('q') . '%';
                $q->where(fn ($w) => $w->where('customer_name', 'like', $term)
                    ->orWhere('customer_contact_no', 'like', $term)
                    ->orWhere('staff_name', 'like', $term)
                    ->when(ctype_digit((string) $request->query('q')), fn ($x) => $x->orWhere('operation_lead_id', (int) $request->query('q'))));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $averages = (clone $base)->selectRaw(
            collect(array_keys(CustomerFeedback::RATING_FIELDS))->map(fn ($f) => "AVG($f) as $f")->implode(', ') . ', COUNT(*) as total'
        )->first();

        return view('staff_feedback.index', [
            'layout' => $this->layout($request),
            'rp' => $this->routePrefix($request),
            'isManager' => $isManager,
            'feedbacks' => $feedbacks,
            'averages' => $averages,
            'tab' => $tab,
            'unseenCount' => (clone $base)->whereNull($this->seenColumn($isManager))->count(),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $isManager = $this->isManager($request);
        $feedback = CustomerFeedback::query()
            ->visibleToOperation($request->user(), $isManager)
            ->with(['operationLead', 'operationUser:id,f_name,l_name', 'operationManager:id,f_name,l_name'])
            ->findOrFail($id);

        $column = $this->seenColumn($isManager);
        if (! $feedback->$column) {
            $feedback->forceFill([$column => now()])->saveQuietly();
        }

        return view('staff_feedback.show', [
            'layout' => $this->layout($request),
            'rp' => $this->routePrefix($request),
            'feedback' => $feedback,
        ]);
    }

    public static function unseenCountFor($user, bool $isManager): int
    {
        return CustomerFeedback::query()
            ->visibleToOperation($user, $isManager)
            ->whereNull($isManager ? 'manager_seen_at' : 'operation_seen_at')
            ->count();
    }

    private function isManager(Request $request): bool
    {
        return $request->routeIs('operation-manager.*');
    }

    private function seenColumn(bool $isManager): string
    {
        return $isManager ? 'manager_seen_at' : 'operation_seen_at';
    }

    private function layout(Request $request): string
    {
        return $this->isManager($request) ? 'operation_manager.layouts.app' : 'operation.layouts.app';
    }

    private function routePrefix(Request $request): string
    {
        return $this->isManager($request) ? 'operation-manager' : 'operation';
    }
}
