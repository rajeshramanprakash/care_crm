<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesBrokerPortalUser;
use App\Http\Controllers\Concerns\ManagesPartnerCorporateAccounts;
use App\Http\Controllers\Controller;
use App\Models\BrokerUser;
use App\Models\CorporateEmployee;
use App\Models\CorporateUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class BrokerPortalApiController extends Controller
{
    use ManagesPartnerCorporateAccounts;
    use ResolvesBrokerPortalUser;

    private ?BrokerUser $resolvedBroker = null;

    protected function partnerOwnerType(): string
    {
        return 'broker';
    }

    protected function partnerOwnerId(): ?int
    {
        return $this->resolvedBroker?->id;
    }

    private function bootBroker(Request $request): BrokerUser
    {
        return $this->resolvedBroker = $this->brokerFromRequest($request);
    }

    private function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /** @return array<string, mixed> */
    private function brokerProfile(BrokerUser $broker): array
    {
        $companyDocs = is_array($broker->company_documents) ? array_values(array_filter($broker->company_documents)) : [];

        return [
            'id' => $broker->id,
            'name' => $broker->name,
            'company_name' => $broker->company_name,
            'username' => $broker->username,
            'email' => $broker->email,
            'mobile' => $broker->mobile,
            'is_active' => (bool) $broker->is_active,
            'portal_label' => 'Broker Portal',
            'mou_file_url' => $this->storageUrl($broker->mou_file),
            'company_documents' => array_map(fn (string $doc) => [
                'name' => basename($doc),
                'url' => $this->storageUrl($doc),
            ], $companyDocs),
            'corporate_login_url' => url('/corporate/login'),
            'employee_login_url' => url('/corporate/employee/login'),
        ];
    }

    /** @return array<string, mixed> */
    private function corporatePayload(CorporateUser $corporate): array
    {
        return [
            'id' => $corporate->id,
            'corporate_name' => $corporate->corporate_name,
            'username' => $corporate->username,
            'is_active' => (bool) $corporate->is_active,
            'employees_count' => (int) ($corporate->employees_count ?? 0),
            'created_at' => $corporate->created_at?->format('d M Y, h:i A'),
            'updated_at' => $corporate->updated_at?->format('d M Y, h:i A'),
        ];
    }

    /** @return array<string, mixed> */
    private function employeeListPayload(CorporateEmployee $employee): array
    {
        return [
            'id' => $employee->id,
            'employee_id' => $employee->employee_id,
            'employee_name' => $employee->employee_name,
            'phone_number' => $employee->phone_number,
            'email' => $employee->email,
            'si_limit' => $employee->si_limit,
            'active_from' => $employee->active_from?->format('d M Y'),
            'active_to' => $employee->active_to?->format('d M Y'),
            'policy_terms_url' => $employee->policyTermsUrl(),
            'is_active' => (bool) $employee->is_active,
        ];
    }

    /** @return array<string, mixed> */
    private function employeeDetailPayload(CorporateEmployee $employee, CorporateUser $corporate): array
    {
        $corporate->loadMissing(['insurer:id,name,company_name', 'broker:id,name,company_name']);

        return [
            ...$this->employeeListPayload($employee),
            'corporate_name' => $corporate->corporate_name,
            'corporate_username' => $corporate->username,
            'created_by_label' => $corporate->createdByLabel(),
            'date_of_birth' => $employee->date_of_birth?->format('d M Y'),
            'gender' => $employee->gender,
            'relationship' => $employee->relationship,
            'issuance_date' => $employee->issuance_date?->format('d M Y'),
            'last_working_date' => $employee->last_working_date?->format('d M Y'),
            'room_limit' => $employee->room_limit,
            'employee_login_url' => url('/corporate/employee/login'),
            'created_at' => $employee->created_at?->format('d M Y, h:i A'),
            'updated_at' => $employee->updated_at?->format('d M Y, h:i A'),
        ];
    }

    public function dashboard(Request $request)
    {
        $broker = $this->bootBroker($request);

        return response()->json([
            'profile' => $this->brokerProfile($broker),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $broker = $this->bootBroker($request);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        if (! $broker->checkPassword($data['current_password'])) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
                'errors' => ['current_password' => ['Current password is incorrect.']],
            ], 422);
        }

        $broker->password = $data['password'];
        $broker->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    public function corporatesIndex(Request $request)
    {
        $this->bootBroker($request);
        $items = $this->corporateQuery()->withCount('employees')->orderByDesc('id')->get();

        return response()->json([
            'corporate_login_url' => url('/corporate/login'),
            'items' => $items->map(fn (CorporateUser $item) => $this->corporatePayload($item))->values(),
        ]);
    }

    public function corporatesShow(Request $request, int $corporate)
    {
        $this->bootBroker($request);
        $item = $this->findOwnedCorporate($corporate);

        return response()->json([
            'item' => $this->corporatePayload($item),
            'corporate_login_url' => url('/corporate/login'),
        ]);
    }

    public function corporatesStore(Request $request)
    {
        $this->bootBroker($request);
        $data = $this->assignOwner($this->validateCorporatePayload($request));
        $item = CorporateUser::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Corporate account created. Share login link and credentials with the corporate user.',
            'item' => $this->corporatePayload($item->loadCount('employees')),
        ], 201);
    }

    public function corporatesUpdate(Request $request, int $corporate)
    {
        $this->bootBroker($request);
        $item = $this->findOwnedCorporate($corporate);
        $data = $this->validateCorporatePayload($request, $item->id);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $item->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Corporate account updated.',
            'item' => $this->corporatePayload($item->fresh()->loadCount('employees')),
        ]);
    }

    public function corporatesDestroy(Request $request, int $corporate)
    {
        $this->bootBroker($request);
        $this->findOwnedCorporate($corporate)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Corporate account removed.',
        ]);
    }

    public function corporateEmployees(Request $request, int $corporate)
    {
        $this->bootBroker($request);
        $corp = $this->findOwnedCorporate($corporate);
        $items = CorporateEmployee::query()
            ->where('corporate_user_id', $corp->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'corporate' => $this->corporatePayload($corp->loadCount('employees')),
            'items' => $items->map(fn (CorporateEmployee $emp) => $this->employeeListPayload($emp))->values(),
        ]);
    }

    public function corporateEmployeeShow(Request $request, int $corporate, int $employee)
    {
        $this->bootBroker($request);
        $corp = $this->findOwnedCorporate($corporate);
        $emp = CorporateEmployee::query()
            ->where('corporate_user_id', $corp->id)
            ->whereKey($employee)
            ->firstOrFail();

        return response()->json([
            'detail' => $this->employeeDetailPayload($emp, $corp),
        ]);
    }
}
