<?php

declare(strict_types=1);

namespace App\Mail;

use App\Data\Amount;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent when a payment was refunded (payments.refunded): in full because the order could not be completed, or, for
 * reason external, by an admin in Stripe (any amount, possibly one of several partial refunds).
 */
final class RefundMail extends Mailable implements TemplatedMail
{
    public function __construct(
        public readonly ?string $customerName,
        public readonly Amount $amount,
        public readonly string $reason,
    ) {}

    public function template(): string
    {
        return 'refund';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Shopwise payment was refunded');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.refund', with: [
            // An external refund (issued in Stripe, maybe partial) belongs to an order that still exists.
            'checkoutFailed' => $this->reason !== 'external',
            'explanation' => match ($this->reason) {
                'reservation_expired' => 'the items were no longer reserved for you by the time your payment went through',
                'order_rejected' => 'we could not place your order',
                'external' => null,
                default => 'your checkout took too long to complete',
            },
        ]);
    }
}
