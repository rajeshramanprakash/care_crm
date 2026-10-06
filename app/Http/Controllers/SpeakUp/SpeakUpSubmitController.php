<?php

namespace App\Http\Controllers\SpeakUp;

use App\Http\Controllers\Controller;
use App\Models\SpeakUpSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Staff / Sub Admin / freelancer / vendor side of Speak Up.
 * Anonymous submissions store no name, id, role or IP — only the reference number and a hashed follow-up key.
 */
class SpeakUpSubmitController extends Controller
{
    public function create(Request $request)
    {
        return view('speak_up.create', ['submitter' => $this->submitter($request)]);
    }

    public function store(Request $request)
    {
        $submitter = $this->submitter($request);
        $data = $request->validate([
            'category' => 'required|in:' . implode(',', array_keys(SpeakUpSubmission::CATEGORIES)),
            'subject' => 'required|string|max:150',
            'message' => 'required|string|max:5000',
            'is_anonymous' => 'nullable|boolean',
        ]);
        $anonymous = $request->boolean('is_anonymous');
        $followUpKey = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));

        $submission = DB::transaction(fn () => SpeakUpSubmission::create([
            'reference_no' => SpeakUpSubmission::nextReferenceNo(),
            'follow_up_key_hash' => Hash::make($followUpKey),
            'category' => $data['category'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'is_anonymous' => $anonymous,
            'submitter_type' => $anonymous ? null : $submitter['type'],
            'submitter_id' => $anonymous ? null : $submitter['id'],
            'submitter_name' => $anonymous ? null : $submitter['name'],
            'submitter_role' => $anonymous ? null : $submitter['role'],
            'status' => 'new',
        ]));

        return redirect()->route('speak_up.submitted')->with('speak_up_receipt', [
            'reference_no' => $submission->reference_no,
            'follow_up_key' => $followUpKey,
            'is_anonymous' => $anonymous,
        ]);
    }

    public function submitted(Request $request)
    {
        $receipt = $request->session()->get('speak_up_receipt');
        if (! $receipt) {
            return redirect()->route('speak_up.create');
        }

        return view('speak_up.submitted', ['submitter' => $this->submitter($request), 'receipt' => $receipt]);
    }

    public function mine(Request $request)
    {
        $submitter = $this->submitter($request);
        $submissions = SpeakUpSubmission::query()
            ->where('is_anonymous', false)
            ->where('submitter_type', $submitter['type'])
            ->where('submitter_id', $submitter['id'])
            ->latest()
            ->paginate(15);

        return view('speak_up.mine', compact('submitter', 'submissions'));
    }

    public function showMine(Request $request, int $id)
    {
        $submitter = $this->submitter($request);
        $submission = SpeakUpSubmission::query()
            ->where('id', $id)
            ->where('is_anonymous', false)
            ->where('submitter_type', $submitter['type'])
            ->where('submitter_id', $submitter['id'])
            ->with('publicReplies')
            ->firstOrFail();
        $this->markRead($submission);

        return view('speak_up.status', compact('submitter', 'submission'));
    }

    public function statusForm(Request $request)
    {
        return view('speak_up.status', ['submitter' => $this->submitter($request), 'submission' => null]);
    }

    public function statusCheck(Request $request)
    {
        $submitter = $this->submitter($request);
        $data = $request->validate([
            'reference_no' => 'required|string|max:30',
            'follow_up_key' => 'required|string|max:30',
        ]);

        $submission = SpeakUpSubmission::query()
            ->where('reference_no', strtoupper(trim($data['reference_no'])))
            ->with('publicReplies')
            ->first();

        if (! $submission || ! Hash::check(strtoupper(trim($data['follow_up_key'])), $submission->follow_up_key_hash)) {
            return back()->withInput($request->only('reference_no'))
                ->withErrors(['reference_no' => 'Reference number or follow-up key is wrong.']);
        }
        $this->markRead($submission);

        return view('speak_up.status', [
            'submitter' => $submitter,
            'submission' => $submission,
            'followUpKey' => strtoupper(trim($data['follow_up_key'])),
        ]);
    }

    /**
     * Status of anonymous cases whose reference + key the sender's own browser saved (My Submissions).
     * Nothing about the sender is stored on the server; a pair is answered only if the key matches.
     */
    public function savedStatuses(Request $request)
    {
        $items = collect($request->input('items', []))
            ->filter(fn ($item) => is_array($item) && is_string($item['reference_no'] ?? null) && is_string($item['follow_up_key'] ?? null))
            ->take(20);

        $found = SpeakUpSubmission::query()
            ->whereIn('reference_no', $items->map(fn ($i) => strtoupper(trim($i['reference_no'])))->all())
            ->get()
            ->keyBy('reference_no');

        $result = $items->map(function ($item) use ($found) {
            $reference = strtoupper(trim($item['reference_no']));
            $submission = $found->get($reference);
            if (! $submission || ! Hash::check(strtoupper(trim($item['follow_up_key'])), $submission->follow_up_key_hash)) {
                return ['reference_no' => $reference, 'valid' => false];
            }

            return [
                'reference_no' => $reference,
                'valid' => true,
                'subject' => $submission->subject,
                'category' => $submission->category_label,
                'status' => $submission->status,
                'status_label' => $submission->status_label,
                'has_unread_reply' => (bool) $submission->has_unread_reply,
                'date' => $submission->created_at->format('d M Y'),
            ];
        })->values();

        return response()->json(['items' => $result]);
    }

    private function markRead(SpeakUpSubmission $submission): void
    {
        if ($submission->has_unread_reply) {
            $submission->forceFill(['has_unread_reply' => false])->saveQuietly();
        }
    }

    private function submitter(Request $request): array
    {
        return $request->attributes->get('speak_up_submitter');
    }
}
