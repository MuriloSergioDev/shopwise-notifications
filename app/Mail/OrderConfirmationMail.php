<?php

declare(strict_types=1);

namespace App\Mail;

use App\Data\PlacedOrder;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent once an order exists (orders.order-placed): lines, totals and the shipping address.
 */
final class OrderConfirmationMail extends Mailable implements TemplatedMail
{
    public function __construct(public readonly PlacedOrder $order) {}

    public function template(): string
    {
        return 'order-confirmation';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your Shopwise order {$this->order->number}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-confirmation');
    }
}
