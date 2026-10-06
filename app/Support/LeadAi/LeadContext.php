<?php

namespace App\Support\LeadAi;

use App\Models\CustomerFeedback;
use App\Models\Lead;
use App\Models\OperationDeploymentDetails;
use App\Models\OperationLead;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Collects everything known about one sales or operation lead (lead fields, remark history, every call
 * with recording, WhatsApp chat, deployments, payments, feedback, tickets) for the AI.
 */
class LeadContext
{
    public const MAX_WHATSAPP = 120;

    public const MAX_REMARKS = 60;

    public const MAX_CALLS = 80;

    /** @var array<string, mixed> */
    public array $header = [];

    /** @var list<string> */
    public array $remarks = [];

    /** @var list<array{key: string, at: ?Carbon, status: string, raw_status: string, direction: ?string, agent: ?string, duration: ?int, recording_url: ?string, source: string}> */
    public array $calls = [];

    /** @var list<string> */
    public array $whatsapp = [];

    /** @var list<string> */
    public array $extra = [];

    public function __construct(
        public string $type,
        public Lead|OperationLead $lead,
    ) {}

    public static function for(string $type, int $id): ?self
    {
        $lead = $type === 'operation' ? OperationLead::withTrashed()->find($id) : Lead::find($id);
        if (! $lead) {
            return null;
        }

        $context = new self($type, $lead);
        $type === 'operation' ? $context->buildOperation() : $context->buildSales();
        $context->calls = self::mergeCalls($context->calls);
        $context->whatsapp = $context->loadWhatsapp();

        return $context;
    }

    public function mobile(): string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->lead->contact_no);

        return strlen($digits) >= 10 ? substr($digits, -10) : '';
    }

    /** Fingerprint of the data; a new call, remark, chat or status change gives a new hash. */
    public function hash(): string
    {
        return hash('sha256', json_encode([array_diff_key($this->header, ['Last updated' => 1]), $this->remarks, array_map(fn ($c) => [$c['key'], $c['status'], $c['duration']], $this->calls), $this->whatsapp, $this->extra]));
    }

    public function hasConversation(): bool
    {
        return $this->remarks !== [] || $this->whatsapp !== []
            || collect($this->calls)->contains(fn ($c) => $c['status'] === 'answered');
    }

    /** Any call (even missed), remark, chat or operation record — enough for an automatic review. */
    public function hasActivity(): bool
    {
        return $this->remarks !== [] || $this->whatsapp !== [] || $this->calls !== [] || $this->extra !== [];
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        $calls = collect($this->calls);

        return [
            'calls' => $calls->count(),
            'answered' => $calls->where('status', 'answered')->count(),
            'missed' => $calls->whereIn('status', ['missed', 'busy', 'no_answer', 'cancelled'])->count(),
            'recordings' => $calls->where('status', 'answered')->filter(fn ($c) => $c['recording_url'])->count(),
            'whatsapp' => count($this->whatsapp),
            'remarks' => count($this->remarks),
        ];
    }

    private function buildSales(): void
    {
        /** @var Lead $lead */
        $lead = $this->lead;
        $executive = $this->userWithManager($lead->executive);

        $this->header = array_filter([
            'Lead' => $lead->formatted_id.' (#'.$lead->id.')',
            'Created' => $this->fmt($lead->created_at ?? $lead->date),
            'Lead source' => $lead->lead_source,
            'Service asked' => $lead->query,
            'Location' => $lead->location,
            'Patient' => trim(implode(', ', array_filter([$lead->patient_name, $lead->age ? $lead->age.' yrs' : null, $lead->patient_gender]))),
            'Current status' => $lead->status,
            'Stage' => $lead->stage,
            'Shift type' => $lead->shift_type,
            'Rate quoted / prospect rate' => $lead->prospect_rate,
            'Follow-up date' => $this->fmt($lead->follow_up_date),
            'Future prospect date' => $this->fmt($lead->future_prospect_date),
            'Last call status' => $lead->last_call_status,
            'Status remark' => $lead->status_remarks,
            'Query remark' => $lead->query_remarks,
            'Inactive remark' => $lead->inactive_stage_remark,
            'Sales executive' => $executive['name'],
            'Sales manager' => $executive['manager'],
            'Last updated' => $this->fmt($lead->updated_at),
        ], fn ($v) => $v !== null && $v !== '');

        $operation = OperationLead::query()
            ->where(fn ($q) => $q->where('lead_id', (string) $lead->id)->orWhere('lead_id', $lead->formatted_id))
            ->latest('id')
            ->first();
        if ($operation) {
            $this->extra[] = 'Converted to operation lead #'.$operation->id.' — operation status: '.($operation->status ?: '-')
                .($operation->closed_rate ? ', closed rate ₹'.$operation->closed_rate : '');
        }

        $this->remarks = $lead->statusRemarks()->latest('id')->limit(self::MAX_REMARKS)->get()->reverse()
            ->map(fn ($r) => $this->fmt($r->created_at).' | '.($r->created_by_name ?: 'Staff').' | status: '.($r->status_at_remark ?: '-').' | '.Str::limit((string) $r->remark, 600))
            ->values()->all();

        $this->loadCalls('lead', (int) $lead->id, $lead->recording_url);
    }

    private function buildOperation(): void
    {
        /** @var OperationLead $lead */
        $lead = $this->lead;
        $executive = $this->userWithManager($lead->executive);

        $this->header = array_filter([
            'Operation lead' => '#'.$lead->id.($lead->lead_id ? ' (sales lead '.$lead->lead_id.')' : ''),
            'Created' => $this->fmt($lead->date_time ?? $lead->created_at),
            'Service' => $lead->query,
            'Location' => trim(implode(', ', array_filter([$lead->address, $lead->location]))),
            'Patient' => trim(implode(', ', array_filter([$lead->patient_name, $lead->age ? $lead->age.' yrs' : null, $lead->patient_gender]))),
            'Current status' => $lead->status,
            'Ongoing / stopped' => $lead->ongoing_stopped,
            'Shift type' => $lead->shift_type,
            'Customer rate (closed rate)' => $lead->closed_rate,
            'Vendor / staff rate' => $lead->vendor_closed_rate,
            'Payment plan' => $lead->payment_plan,
            'Follow-up date' => $this->fmt($lead->follow_up_date),
            'Last call status' => $lead->last_call_status,
            'Status remark' => $lead->status_remark,
            'Price issue remark' => $lead->price_issue_remark,
            'Inactive remark' => $lead->inactive_remark,
            'Closed remark' => $lead->closed_remark,
            'Stopped remark' => $lead->stopped_remark,
            'Query remark' => $lead->query_remark,
            'Operation executive' => $executive['name'],
            'Operation manager' => $executive['manager'],
            'Last updated' => $this->fmt($lead->updated_at),
        ], fn ($v) => $v !== null && $v !== '');

        $remarks = $lead->statusRemarks()->latest('id')->limit(self::MAX_REMARKS)->get()->reverse()
            ->map(fn ($r) => $this->fmt($r->created_at).' | Operation | '.($r->created_by_name ?: 'Staff').' | status: '.($r->status_at_remark ?: '-').' | '.Str::limit((string) $r->remark, 600));
        $crm = $lead->resolveCrmLead();
        $salesRemarks = $crm ? $crm->statusRemarks()->latest('id')->limit(15)->get()->reverse()
            ->map(fn ($r) => $this->fmt($r->created_at).' | Sales | '.($r->created_by_name ?: 'Staff').' | status: '.($r->status_at_remark ?: '-').' | '.Str::limit((string) $r->remark, 400)) : collect();
        $this->remarks = $salesRemarks->concat($remarks)->values()->all();

        foreach (OperationDeploymentDetails::with(['vendor:id,name', 'freelanceStaff'])->where('operation_lead_id', $lead->id)->orderBy('id')->get() as $d) {
            $absent = is_array($d->absent_dates) ? count($d->absent_dates) : (is_string($d->absent_dates) && $d->absent_dates !== '' ? count(array_filter(explode(',', trim($d->absent_dates, '[]"')))) : 0);
            $this->extra[] = 'Deployment #'.$d->id.': '.($d->deployment_status ?: '-')
                .' | staff: '.($d->staff_name ?: optional($d->freelanceStaff)->name ?: '-')
                .' | via: '.($d->vendor_id ? 'vendor '.optional($d->vendor)->name : ($d->freelance_staff_id ? 'freelancer' : '-'))
                .' | '.$this->fmt($d->deployment_from_date ?? $d->deployment_date).' → '.($this->fmt($d->deployment_to_date) ?: 'ongoing')
                .($d->duty_hours ? ' | duty: '.$d->duty_hours.(is_numeric($d->duty_hours) ? ' hrs' : '') : '')
                .($d->vendor_rate_per_day ? ' | staff rate/day ₹'.$d->vendor_rate_per_day : '')
                .($absent ? ' | absent days: '.$absent : '')
                .($d->remark ? ' | remark: '.Str::limit((string) $d->remark, 300) : '');
        }

        $invoices = DB::table('payment_invoices')->where('operation_lead_id', $lead->id)->get(['payment_amount', 'is_received', 'from_date', 'to_date']);
        if ($invoices->isNotEmpty()) {
            $received = (float) DB::table('received_payments')->where('operation_lead_id', $lead->id)->sum('amount');
            $billed = (float) $invoices->sum('payment_amount');
            $this->extra[] = 'Payments: '.$invoices->count().' invoices, billed ₹'.number_format($billed).', received ₹'.number_format($received)
                .', pending ₹'.number_format(max(0, $billed - $received)).', unpaid invoices: '.$invoices->where('is_received', 0)->count();
        }

        foreach (CustomerFeedback::where('operation_lead_id', $lead->id)->get() as $f) {
            $this->extra[] = 'Customer feedback '.$this->fmt($f->created_at).': overall '.$f->overall_rating.'/5'
                .collect(['service_quality' => 'quality', 'staff_behaviour' => 'staff', 'response_time' => 'response'])->filter(fn ($l, $k) => $f->$k)->map(fn ($l, $k) => ", $l {$f->$k}/5")->implode('')
                .($f->complaint ? ' | complaint: '.Str::limit($f->complaint, 400) : '')
                .($f->suggestions ? ' | suggestion: '.Str::limit($f->suggestions, 300) : '');
        }

        if ($this->mobile() !== '') {
            $tickets = SupportTicket::query()->where(fn ($q) => $this->matchMobile($q, 'customer_contact_no'))->latest('id')->limit(10)->get();
            foreach ($tickets as $t) {
                $this->extra[] = 'Support ticket '.$t->ticket_no.' ('.$this->fmt($t->created_at).'): '.$t->category_label.' — '.Str::limit($t->subject, 150).' — status '.$t->status_label;
            }
        }

        $this->loadCalls('operation_lead', (int) $lead->id, $lead->recording_url);
        if ($lead->active_deployment_recording_url) {
            $this->calls = array_merge($this->calls, $this->recordingJsonCalls($lead->active_deployment_recording_url));
        }
    }

    private function loadCalls(string $callFor, int $leadId, $recordingJson): void
    {
        $mobile = $this->mobile();

        $details = DB::table('call_details')
            ->where(function ($q) use ($callFor, $leadId, $mobile) {
                $q->where(fn ($w) => $w->where('lead_id', $leadId)->where(fn ($x) => $x->where('call_for', $callFor)->when($callFor === 'lead', fn ($y) => $y->orWhereNull('call_for'))));
                if ($mobile !== '') {
                    $q->orWhere(fn ($w) => $this->matchMobile($w, 'caller_id_number'));
                }
            })
            ->orderByDesc('call_received_datetime')
            ->limit(self::MAX_CALLS)
            ->get(['id', 'call_status', 'call_type', 'agent_name', 'call_duration', 'recording_url', 'call_received_datetime', 'raw_data']);

        foreach ($details as $row) {
            $raw = is_string($row->raw_data) ? json_decode($row->raw_data, true) : null;
            $this->calls[] = $this->call(
                $raw['call_id'] ?? self::callIdFromUrl($row->recording_url) ?? 'cd'.$row->id,
                $row->call_received_datetime,
                (string) $row->call_status,
                $row->call_type ?: ($raw['direction'] ?? null),
                $row->agent_name ?: ($raw['answered_agent_name'] ?? $raw['missed_agent']['name'] ?? null),
                $row->call_duration !== null ? (int) $row->call_duration : (isset($raw['billsec']) ? (int) $raw['billsec'] : null),
                $row->recording_url ?: ($raw['recording_url'] ?? null),
                'call_details',
            );
        }

        if ($callFor === 'lead') {
            $logs = DB::table('call_logs')
                ->where(function ($q) use ($leadId, $mobile) {
                    $q->where('lead_id', $leadId);
                    if ($mobile !== '') {
                        $q->orWhere(fn ($w) => $this->matchMobile($w, 'caller_id_number'));
                    }
                })
                ->orderByDesc('call_received_datetime')
                ->limit(self::MAX_CALLS)
                ->get(['id', 'tata_call_id', 'call_status', 'agent_name', 'recording_url', 'call_received_datetime']);
            foreach ($logs as $row) {
                $this->calls[] = $this->call($row->tata_call_id ?: self::callIdFromUrl($row->recording_url) ?? 'cl'.$row->id, $row->call_received_datetime, (string) $row->call_status, null, $row->agent_name, null, $row->recording_url, 'call_logs');
            }
        }

        $this->calls = array_merge($this->calls, $this->recordingJsonCalls($recordingJson));
    }

    /** Legacy JSON list stored on leads.recording_url / operation_leads.recording_url. */
    private function recordingJsonCalls($json): array
    {
        $items = is_string($json) ? json_decode($json, true) : (is_array($json) ? $json : null);
        if (! is_array($items)) {
            return [];
        }

        $calls = [];
        foreach ($items as $item) {
            if (! is_array($item) || empty($item['url'])) {
                continue;
            }
            $meta = is_string($item['metadata'] ?? null) ? json_decode($item['metadata'], true) : ($item['metadata'] ?? []);
            $meta = is_array($meta) ? $meta : [];
            $calls[] = $this->call(
                $meta['call_id'] ?? self::callIdFromUrl($item['url']) ?? 'url'.substr(sha1($item['url']), 0, 16),
                $meta['datetime'] ?? null,
                (string) ($meta['dialstatus'] ?? ''),
                null,
                isset($meta['caller_agent']) ? str_replace('+', ' ', $meta['caller_agent']) : null,
                isset($meta['duration']) && is_numeric($meta['duration']) ? (int) $meta['duration'] : null,
                $item['url'],
                'lead_recordings',
            );
        }

        return $calls;
    }

    private function call(string $key, $at, string $rawStatus, ?string $direction, ?string $agent, ?int $duration, ?string $url, string $source): array
    {
        return [
            'key' => (string) $key,
            'at' => $at ? Carbon::parse($at) : null,
            'status' => self::normalizeStatus($rawStatus),
            'raw_status' => $rawStatus,
            'direction' => $direction ? strtolower($direction) : null,
            'agent' => $agent ? str_replace('+', ' ', $agent) : null,
            'duration' => $duration,
            'recording_url' => $url ?: null,
            'source' => $source,
        ];
    }

    /** One entry per real call (the same call can be in call_details, call_logs and the lead JSON). */
    private static function mergeCalls(array $calls): array
    {
        $rank = ['answered' => 5, 'missed' => 4, 'busy' => 4, 'no_answer' => 4, 'cancelled' => 4, 'other' => 2, 'ringing' => 1];
        $merged = [];
        foreach ($calls as $call) {
            $existing = $merged[$call['key']] ?? null;
            if (! $existing) {
                $merged[$call['key']] = $call;

                continue;
            }
            $better = ($rank[$call['status']] ?? 0) > ($rank[$existing['status']] ?? 0) ? $call : $existing;
            foreach (['at', 'direction', 'agent', 'duration', 'recording_url'] as $field) {
                $better[$field] ??= $call[$field] ?? $existing[$field];
            }
            $merged[$call['key']] = $better;
        }

        return collect($merged)->sortBy(fn ($c) => $c['at']?->timestamp ?? 0)->values()->take(-self::MAX_CALLS)->values()->all();
    }

    public static function normalizeStatus(string $status): string
    {
        $s = strtolower(trim($status));

        return match (true) {
            in_array($s, ['answer', 'answered', 'connected', 'completed'], true) => 'answered',
            in_array($s, ['missed', 'missed call'], true) => 'missed',
            in_array($s, ['busy', 'executive busy'], true) => 'busy',
            in_array($s, ['noanswer', 'no answer', 'no_answer'], true) => 'no_answer',
            in_array($s, ['cancel', 'cancelled', 'canceled'], true) => 'cancelled',
            $s === 'ringing' => 'ringing',
            default => 'other',
        };
    }

    public static function callIdFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return ! empty($query['callId']) ? (string) $query['callId'] : null;
    }

    private function loadWhatsapp(): array
    {
        $mobile = $this->mobile();
        if ($mobile === '') {
            return [];
        }

        $staff = [];

        return DB::table('whatsapp_messages')
            ->whereNull('deleted_at')
            ->where(fn ($q) => $this->matchMobile($q, 'msg_from'))
            ->orderByDesc('time')
            ->limit(self::MAX_WHATSAPP)
            ->get(['time', 'type', 'body', 'is_sent', 'sent_by'])
            ->reverse()
            ->map(function ($m) use (&$staff) {
                $who = 'Customer';
                if ($m->is_sent) {
                    $id = (int) $m->sent_by;
                    $staff[$id] ??= $id ? (trim((string) optional(User::withTrashed()->find($id))->f_name) ?: 'Staff') : 'Carelix';
                    $who = 'Carelix ('.$staff[$id].')';
                }
                $text = match ($m->type) {
                    'text', 'template', 'interactive' => Str::limit(trim((string) $m->body), 700),
                    'audio' => '[voice note]',
                    'image', 'video', 'document', 'sticker' => '['.$m->type.']'.($m->body ? ' '.Str::limit((string) $m->body, 200) : ''),
                    default => '['.$m->type.']',
                };

                return $this->fmt($m->time).' | '.$who.': '.$text;
            })
            ->values()
            ->all();
    }

    private function matchMobile($query, string $column)
    {
        $mobile = $this->mobile();

        return $query->whereIn($column, [$mobile, '91'.$mobile, '+91'.$mobile, '0'.$mobile])
            ->orWhereRaw("RIGHT(REPLACE(REPLACE($column, '+', ''), ' ', ''), 10) = ?", [$mobile]);
    }

    /** @return array{name: ?string, manager: ?string} */
    private function userWithManager($userId): array
    {
        $user = is_numeric($userId) ? User::withTrashed()->find((int) $userId) : null;
        if (! $user) {
            return ['name' => $userId ? (string) $userId : null, 'manager' => null];
        }
        $manager = $user->parent_id ? User::withTrashed()->find($user->parent_id) : null;

        return [
            'name' => trim($user->f_name.' '.$user->l_name),
            'manager' => $manager ? trim($manager->f_name.' '.$manager->l_name) : null,
        ];
    }

    private function fmt($value): ?string
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
