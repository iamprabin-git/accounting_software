<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminUserAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.config('app.name').'] '.__('New user account'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin-user-account-created',
        );
    }
}
