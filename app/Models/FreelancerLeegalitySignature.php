<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreelancerLeegalitySignature extends Model
{
    public const STATUS_SENT = 'SENT';
    public const STATUS_SIGNED = 'SIGNED';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'job_request_id',
        'leegality_document_id',
        'irn',
        'signature_status',
        'document_status',
        'signer_action',
        'signer_email',
        'signer_name',
        'sign_url',
        'sent_at',
        'signed_at',
        'signed_document',
        'audit_trail',
        'create_response',
        'last_webhook_payload',
        'last_webhook_at',
        'sent_by',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'signed_at' => 'datetime',
        'last_webhook_at' => 'datetime',
        'create_response' => 'array',
        'last_webhook_payload' => 'array',
    ];

    public function jobRequest(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class, 'job_request_id');
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function isSignedLike(): bool
    {
        return in_array($this->signature_status, [
            self::STATUS_SIGNED,
            self::STATUS_APPROVED,
            self::STATUS_COMPLETED,
        ], true);
    }

    public function statusLabel(): string
    {
        return match ($this->signature_status) {
            self::STATUS_SENT => 'Sent',
            self::STATUS_SIGNED => 'Signed',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            default => $this->signature_status ?: '—',
        };
    }

    public function statusEmoji(): string
    {
        return match ($this->signature_status) {
            self::STATUS_SENT => '📤',
            self::STATUS_SIGNED, self::STATUS_APPROVED, self::STATUS_COMPLETED => '✅',
            self::STATUS_REJECTED, self::STATUS_FAILED => '❌',
            default => '•',
        };
    }
}
