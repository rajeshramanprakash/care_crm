<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerFeedback;
use App\Support\CustomerPortal;
use Illuminate\Http\Request;

class CustomerFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $customer = CustomerPortal::current();
        $pendingServices = CustomerPortal::servicesAwaitingFeedback($customer);
        $feedbacks = CustomerFeedback::query()
            ->where('customer_contact_no', $customer['contact_no'])
            ->with('operationLead:id,query,patient_name')
            ->latest()
            ->paginate(10);

        return view('customer.feedback.index', [
            'customer' => $customer,
            'pendingServices' => $pendingServices,
            'feedbacks' => $feedbacks,
            'selectedServiceId' => (int) $request->query('service'),
        ]);
    }

    public function store(Request $request)
    {
        $customer = CustomerPortal::current();

        $rules = [
            'overall_rating' => 'required|integer|min:1|max:5',
            'service_quality' => 'nullable|integer|min:1|max:5',
            'staff_behaviour' => 'nullable|integer|min:1|max:5',
            'response_time' => 'nullable|integer|min:1|max:5',
            'overall_experience' => 'nullable|integer|min:1|max:5',
            'suggestions' => 'nullable|string|max:3000',
            'complaint' => 'nullable|string|max:3000',
            'operation_lead_id' => 'required|integer',
        ];
        $data = $request->validate($rules, [
            'overall_rating.required' => 'Please give an overall rating.',
            'operation_lead_id.required' => 'Please choose the service you are giving feedback for.',
        ]);

        $lead = CustomerPortal::servicesAwaitingFeedback($customer)->firstWhere('id', (int) $data['operation_lead_id']);
        if (! $lead) {
            return back()->withInput()->withErrors(['operation_lead_id' => 'Feedback for this service is already submitted or not available.']);
        }

        CustomerFeedback::create(CustomerPortal::feedbackRouting($lead) + [
            'customer_contact_no' => $customer['contact_no'],
            'customer_type' => $customer['type'],
            'customer_ref_id' => (int) $customer['id'] > 0 ? (int) $customer['id'] : null,
            'customer_name' => $customer['name'],
            'operation_lead_id' => $lead->id,
            'overall_rating' => $data['overall_rating'],
            'service_quality' => $data['service_quality'] ?? null,
            'staff_behaviour' => $data['staff_behaviour'] ?? null,
            'response_time' => $data['response_time'] ?? null,
            'overall_experience' => $data['overall_experience'] ?? null,
            'suggestions' => $data['suggestions'] ?? null,
            'complaint' => $data['complaint'] ?? null,
            'status' => 'new',
        ]);

        return redirect()->route('customer.feedback.index')
            ->with('status', ['alert_type' => 'success', 'message' => 'Thank you! Your feedback has been submitted.']);
    }

    public function dismissPrompt(Request $request)
    {
        $request->session()->put('customer_feedback_prompt_dismissed', true);

        return response()->json(['success' => true]);
    }
}
