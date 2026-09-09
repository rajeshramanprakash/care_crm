<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Concerns\ManagesPartnerCorporateAccounts;
use App\Http\Controllers\Controller;
use App\Models\BrokerUser;
use App\Models\CorporateUser;
use Illuminate\Http\Request;

class BrokerCorporateController extends Controller
{
    use ManagesPartnerCorporateAccounts;

    private function ensureBroker(): ?BrokerUser
    {
        $user = BrokerUser::find(session('broker_user_id'));

        return ($user && $user->is_active) ? $user : null;
    }

    protected function partnerOwnerType(): string
    {
        return 'broker';
    }

    protected function partnerOwnerId(): ?int
    {
        return session('broker_user_id') ? (int) session('broker_user_id') : null;
    }

    public function index()
    {
        if (! $this->ensureBroker()) {
            return redirect()->route('broker.login')->with('error', 'Session expired.');
        }

        $items = $this->corporateQuery()->withCount('employees')->orderByDesc('id')->get();

        return view('broker.corporates.index', [
            'items' => $items,
            'partner_type' => 'broker',
            'login_url' => url('/corporate/login'),
        ]);
    }

    public function create()
    {
        if (! $this->ensureBroker()) {
            return redirect()->route('broker.login')->with('error', 'Session expired.');
        }

        return view('broker.corporates.form', [
            'item' => new CorporateUser(['is_active' => true]),
            'partner_type' => 'broker',
            'login_url' => url('/corporate/login'),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->ensureBroker()) {
            return redirect()->route('broker.login')->with('error', 'Session expired.');
        }

        $data = $this->assignOwner($this->validateCorporatePayload($request));
        CorporateUser::create($data);

        return redirect()->route('broker.corporates.index')->with('success', 'Corporate account created. Share login link and credentials with the corporate user.');
    }

    public function edit(CorporateUser $corporate)
    {
        if (! $this->ensureBroker()) {
            return redirect()->route('broker.login')->with('error', 'Session expired.');
        }

        $this->findOwnedCorporate($corporate->id);

        return view('broker.corporates.form', [
            'item' => $corporate,
            'partner_type' => 'broker',
            'login_url' => url('/corporate/login'),
        ]);
    }

    public function update(Request $request, CorporateUser $corporate)
    {
        if (! $this->ensureBroker()) {
            return redirect()->route('broker.login')->with('error', 'Session expired.');
        }

        $item = $this->findOwnedCorporate($corporate->id);
        $data = $this->validateCorporatePayload($request, $item->id);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $item->update($data);

        return redirect()->route('broker.corporates.index')->with('success', 'Corporate account updated.');
    }

    public function destroy(CorporateUser $corporate)
    {
        if (! $this->ensureBroker()) {
            return redirect()->route('broker.login')->with('error', 'Session expired.');
        }

        $this->findOwnedCorporate($corporate->id)->delete();

        return redirect()->route('broker.corporates.index')->with('success', 'Corporate account removed.');
    }
}
