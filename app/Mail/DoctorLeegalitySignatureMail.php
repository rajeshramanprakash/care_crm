<?php

namespace App\Mail;

use App\Models\DoctorRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DoctorLeegalitySignatureMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DoctorRequest $doctor,
        public string $signUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Service Agreement Signature Request - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.doctor_leegality_signature',
        );
    }
}
