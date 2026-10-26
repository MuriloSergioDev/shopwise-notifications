<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent once a customer registered (identity.customer-registered).
 */
final class WelcomeMail extends Mailable implements TemplatedMail
{
    public function __construct(public readonly string $name) {}

    public function template(): string
    {
        return 'welcome';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to Shopwise');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.welcome', with: [
            'shopUrl' => config()->string('notifications.storefront_url'),
        ]);
    }
}
