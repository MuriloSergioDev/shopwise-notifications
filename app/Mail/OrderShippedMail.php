<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent when an admin marked the order shipped (orders.order-shipped).
 */
final class OrderShippedMail extends Mailable implements TemplatedMail
{
    public function __construct(
        public readonly string $orderNumber,
        public readonly string $carrier,
        public readonly string $trackingNumber,
    ) {}

    public function template(): string
    {
        return 'order-shipped';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your Shopwise order {$this->orderNumber} is on its way");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-shipped');
    }
}
