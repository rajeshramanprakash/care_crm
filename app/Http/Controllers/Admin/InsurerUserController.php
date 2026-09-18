<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesPartnerUserDocuments;
use App\Http\Controllers\Controller;
use App\Models\InsurerUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InsurerUserController extends Controller
{
    use HandlesPartnerUserDocuments;

    public function __construct()
    {
        $this->middleware('can:view_insurers')->only(['index', 'show']);
        $this->middleware('can:create_insurer')->only(['create', 'store']);
        $this->middleware('can:edit_insurer')->only(['edit', 'update']);
        $this->middleware('can:delete_insurer')->only(['destroy']);
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('insurer_users', 'username')->ignore($ignoreId)],
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
        $items = InsurerUser::query()
            ->with(['corporateUsers' => fn ($q) => $q->withCount('employees')->orderByDesc('id')])
            ->withCount('corporateUsers')
            ->orderByDesc('id')
            ->get();

        return view('admin.insurers.index', [
            'items' => $items,
            'page_heading' => 'Insurer Accounts',
            'partner_type' => 'insurer',
            'partner_label' => 'Insurer',
        ]);
    }

    public function create()
    {
        return view('admin.insurers.form', [
            'item' => new InsurerUser(['is_active' => true]),
            'page_heading' => 'Add Insurer',
            'partner_type' => 'insurer',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $user = InsurerUser::create($data);
        $this->processPartnerDocuments($request, $user, 'insurer');

        return redirect()->route('admin.insurers.index')->with('success', 'Insurer account created. They can login with the username and password you set.');
    }

    public function edit(InsurerUser $insurer)
    {
        return view('admin.insurers.form', [
            'item' => $insurer,
            'page_heading' => 'Edit Insurer',
            'partner_type' => 'insurer',
        ]);
    }

    public function update(Request $request, InsurerUser $insurer)
    {
        $data = $this->validatePayload($request, $insurer->id);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $insurer->update($data);
        $this->processPartnerDocuments($request, $insurer->fresh(), 'insurer');

        return redirect()->route('admin.insurers.index')->with('success', 'Insurer account updated.');
    }

    public function destroy(InsurerUser $insurer)
    {
        $this->deletePartnerUserFiles($insurer);
        $insurer->delete();

        return redirect()->route('admin.insurers.index')->with('success', 'Insurer account removed.');
    }
}
