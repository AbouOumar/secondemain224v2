<?php

namespace App\Mail;

use App\Enums\EmailCategory;
use App\Models\NewsletterCampaign;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\URL;

class NewsletterMail extends Mailable
{
    public string $unsubscribeUrl;

    public function __construct(public NewsletterCampaign $campaign, public User $user)
    {
        $this->unsubscribeUrl = URL::signedRoute('email.unsubscribe', [
            'user' => $user->id,
            'category' => EmailCategory::Newsletter->value,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => "<{$this->unsubscribeUrl}>",
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.newsletter');
    }
}
