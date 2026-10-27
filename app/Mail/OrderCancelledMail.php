<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent when checkout undid a placed order (orders.order-cancelled with an order). The payment refund has its own mail.
 */
final class OrderCancelledMail extends Mailable implements TemplatedMail
{
    public function __construct(
        public readonly string $orderNumber,
        public readonly string $reason,
    ) {}

    public function template(): string
    {
        return 'order-cancelled';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your Shopwise order {$this->orderNumber} was cancelled");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-cancelled', with: [
            'explanation' => match ($this->reason) {
                'timeout' => 'your checkout could not be completed in time',
                default => 'your checkout could not be completed',
            },
        ]);
    }
}
