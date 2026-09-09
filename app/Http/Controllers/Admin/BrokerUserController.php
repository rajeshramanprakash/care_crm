<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesPartnerUserDocuments;
use App\Http\Controllers\Controller;
use App\Models\BrokerUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrokerUserController extends Controller
{
    use HandlesPartnerUserDocuments;

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('broker_users', 'username')->ignore($ignoreId)],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($ignoreId) {
            $rules['password'] = ['nullable', 'string', 'min:6', 'max:100'];
        } else {
            $rules['password'] = ['required', 'string', 'min:6', 'max:100'];
        }

        $data = $request->validate(array_merge($rules, $this->partnerDocumentValidationRules()));
        $data['is_active'] = $request->boolean('is_active', true);

        unset($data['mou_file'], $data['company_documents'], $data['remove_company_documents'], $data['remove_mou']);

        return $data;
    }

    public function index()
    {
        $items = BrokerUser::query()
            ->with(['corporateUsers' => fn ($q) => $q->withCount('employees')->orderByDesc('id')])
            ->withCount('corporateUsers')
            ->orderByDesc('id')
            ->get();

        return view('admin.brokers.index', [
            'items' => $items,
            'page_heading' => 'Broker Accounts',
            'partner_type' => 'broker',
            'partner_label' => 'Broker',
        ]);
    }

    public function create()
    {
        return view('admin.brokers.form', [
            'item' => new BrokerUser(['is_active' => true]),
            'page_heading' => 'Add Broker',
            'partner_type' => 'broker',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $user = BrokerUser::create($data);
        $this->processPartnerDocuments($request, $user, 'broker');

        return redirect()->route('admin.brokers.index')->with('success', 'Broker account created. They can login with the username and password you set.');
    }

    public function edit(BrokerUser $broker)
    {
        return view('admin.brokers.form', [
            'item' => $broker,
            'page_heading' => 'Edit Broker',
            'partner_type' => 'broker',
        ]);
    }

    public function update(Request $request, BrokerUser $broker)
    {
        $data = $this->validatePayload($request, $broker->id);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $broker->update($data);
        $this->processPartnerDocuments($request, $broker->fresh(), 'broker');

        return redirect()->route('admin.brokers.index')->with('success', 'Broker account updated.');
    }

    public function destroy(BrokerUser $broker)
    {
        $this->deletePartnerUserFiles($broker);
        $broker->delete();

        return redirect()->route('admin.brokers.index')->with('success', 'Broker account removed.');
    }
}
