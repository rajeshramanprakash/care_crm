<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpeakUpAccessLog;
use App\Models\SpeakUpSetting;
use App\Models\SpeakUpSubmission;
use App\Support\SpeakUpAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SpeakUpSettingsController extends Controller
{
    public function index()
    {
        $setting = SpeakUpSetting::current();

        return view('admin.speak_up_settings.index', [
            'setting' => $setting,
            'secretUrl' => route('speak_up.review.gate', $setting->secret_slug),
            'newCount' => SpeakUpSubmission::where('status', 'new')->count(),
            'totalCount' => SpeakUpSubmission::count(),
            'recentLogs' => SpeakUpAccessLog::orderByDesc('id')->limit(10)->get(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $setting = SpeakUpSetting::current();
        $first = ! $setting->hasPassword();

        $rules = ['password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()]];
        if (! $first) {
            $rules['current_password'] = 'required|string';
        }
        $request->validate($rules, [], ['password' => 'new Speak Up password']);

        if (! $first && ! Hash::check((string) $request->input('current_password'), $setting->password_hash)) {
            SpeakUpAudit::log($request, 'password_change_failed', false);

            return back()->withErrors(['current_password' => 'Current Speak Up password is wrong.']);
        }

        $setting->update([
            'password_hash' => Hash::make($request->input('password')),
            'password_changed_at' => now(),
            'password_changed_by' => $request->user()->id,
        ]);
        SpeakUpAudit::log($request, $first ? 'password_set' : 'password_changed');

        return redirect()->route('admin.speak_up_settings.index')
            ->with('status', ['alert_type' => 'success', 'message' => $first ? 'Speak Up password set.' : 'Speak Up password changed. Everyone has to enter the new password again.']);
    }

    public function regenerateUrl(Request $request)
    {
        $setting = SpeakUpSetting::current();
        if ($setting->hasPassword()) {
            $request->validate(['current_password' => 'required|string']);
            if (! Hash::check((string) $request->input('current_password'), $setting->password_hash)) {
                SpeakUpAudit::log($request, 'password_change_failed', false, null, 'While regenerating URL');

                return back()->withErrors(['regen_password' => 'Speak Up password is wrong.']);
            }
        }

        $setting->update(['secret_slug' => SpeakUpSetting::newSlug()]);
        SpeakUpAudit::log($request, 'url_regenerated');

        return redirect()->route('admin.speak_up_settings.index')
            ->with('status', ['alert_type' => 'success', 'message' => 'New secret URL created. The old URL no longer works.']);
    }
}
