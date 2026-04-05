<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserPasswordChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $ipAddress = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your :app password was changed', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.user-password-changed',
        );
    }
}
