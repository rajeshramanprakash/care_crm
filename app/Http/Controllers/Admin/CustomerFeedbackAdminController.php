<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerFeedback;
use Illuminate\Http\Request;

/**
 * Admin > Feedback & Support Center > Customer Feedback (also mounted under subadmin.* with permissions).
 */
class CustomerFeedbackAdminController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'all');
        $query = CustomerFeedback::query()
            ->with(['operationUser:id,f_name,l_name', 'operationManager:id,f_name,l_name'])
            ->when($tab === 'complaints', fn ($q) => $q->whereNotNull('complaint')->where('complaint', '!=', ''))
            ->when($tab === 'suggestions', fn ($q) => $q->whereNotNull('suggestions')->where('suggestions', '!=', ''))
            ->when($tab === 'low', fn ($q) => $q->where('overall_rating', '<=', 2))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('to')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->query('q') . '%';
                $q->where(fn ($w) => $w->where('customer_name', 'like', $term)->orWhere('customer_contact_no', 'like', $term));
            });

        $averages = CustomerFeedback::query()->selectRaw(
            collect(array_keys(CustomerFeedback::RATING_FIELDS))->map(fn ($f) => "AVG($f) as $f")->implode(', ') . ', COUNT(*) as total'
        )->first();

        return view('admin.customer_feedback.index', [
            'rp' => $this->prefix($request),
            'feedbacks' => $query->latest()->paginate(20)->withQueryString(),
            'averages' => $averages,
            'tab' => $tab,
            'newCount' => CustomerFeedback::where('status', 'new')->count(),
        ]);
    }

    public function show(Request $request, int $id)
    {
        return view('admin.customer_feedback.show', [
            'rp' => $this->prefix($request),
            'feedback' => CustomerFeedback::with(['operationLead', 'reviewer', 'operationUser:id,f_name,l_name', 'operationManager:id,f_name,l_name'])->findOrFail($id),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(CustomerFeedback::STATUSES)),
            'admin_note' => 'nullable|string|max:3000',
        ]);
        $feedback = CustomerFeedback::findOrFail($id);
        $feedback->update($data + ['reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

        return redirect()->route($this->prefix($request) . '.customer_feedback.show', $feedback->id)
            ->with('status', ['alert_type' => 'success', 'message' => 'Feedback updated.']);
    }

    private function prefix(Request $request): string
    {
        return $request->routeIs('subadmin.*') ? 'subadmin' : 'admin';
    }
}
