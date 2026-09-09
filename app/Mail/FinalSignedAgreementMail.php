<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class FinalSignedAgreementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $userRole,
        public string $signedPdfPath
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Fully Executed Service Agreement - Carelix Healthcare',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.final_signed_agreement',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->signedPdfPath && Storage::disk('public')->exists($this->signedPdfPath)) {
            $fullPath = Storage::disk('public')->path($this->signedPdfPath);
            return [
                Attachment::fromPath($fullPath)
                    ->as('Carelix-Final-Signed-Service-Agreement.pdf')
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
