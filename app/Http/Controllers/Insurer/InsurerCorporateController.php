<?php

namespace App\Http\Controllers\Insurer;

use App\Http\Controllers\Concerns\ManagesPartnerCorporateAccounts;
use App\Http\Controllers\Controller;
use App\Models\CorporateUser;
use App\Models\InsurerUser;
use Illuminate\Http\Request;

class InsurerCorporateController extends Controller
{
    use ManagesPartnerCorporateAccounts;

    private function ensureInsurer(): ?InsurerUser
    {
        $user = InsurerUser::find(session('insurer_user_id'));

        return ($user && $user->is_active) ? $user : null;
    }

    protected function partnerOwnerType(): string
    {
        return 'insurer';
    }

    protected function partnerOwnerId(): ?int
    {
        return session('insurer_user_id') ? (int) session('insurer_user_id') : null;
    }

    public function index()
    {
        if (! $this->ensureInsurer()) {
            return redirect()->route('insurer.login')->with('error', 'Session expired.');
        }

        $items = $this->corporateQuery()->withCount('employees')->orderByDesc('id')->get();

        return view('insurer.corporates.index', [
            'items' => $items,
            'partner_type' => 'insurer',
            'login_url' => url('/corporate/login'),
        ]);
    }

    public function create()
    {
        if (! $this->ensureInsurer()) {
            return redirect()->route('insurer.login')->with('error', 'Session expired.');
        }

        return view('insurer.corporates.form', [
            'item' => new CorporateUser(['is_active' => true]),
            'partner_type' => 'insurer',
            'login_url' => url('/corporate/login'),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->ensureInsurer()) {
            return redirect()->route('insurer.login')->with('error', 'Session expired.');
        }

        $data = $this->assignOwner($this->validateCorporatePayload($request));
        CorporateUser::create($data);

        return redirect()->route('insurer.corporates.index')->with('success', 'Corporate account created. Share login link and credentials with the corporate user.');
    }

    public function edit(CorporateUser $corporate)
    {
        if (! $this->ensureInsurer()) {
            return redirect()->route('insurer.login')->with('error', 'Session expired.');
        }

        $this->findOwnedCorporate($corporate->id);

        return view('insurer.corporates.form', [
            'item' => $corporate,
            'partner_type' => 'insurer',
            'login_url' => url('/corporate/login'),
        ]);
    }

    public function update(Request $request, CorporateUser $corporate)
    {
        if (! $this->ensureInsurer()) {
            return redirect()->route('insurer.login')->with('error', 'Session expired.');
        }

        $item = $this->findOwnedCorporate($corporate->id);
        $data = $this->validateCorporatePayload($request, $item->id);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $item->update($data);

        return redirect()->route('insurer.corporates.index')->with('success', 'Corporate account updated.');
    }

    public function destroy(CorporateUser $corporate)
    {
        if (! $this->ensureInsurer()) {
            return redirect()->route('insurer.login')->with('error', 'Session expired.');
        }

        $this->findOwnedCorporate($corporate->id)->delete();

        return redirect()->route('insurer.corporates.index')->with('success', 'Corporate account removed.');
    }
}
