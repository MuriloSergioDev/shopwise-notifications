<?php

declare(strict_types=1);

namespace App\Messaging\Handlers;

use App\Actions\QueueMail;
use App\Data\Amount;
use App\Mail\RefundMail;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Shopwise\Contracts\Events\Payments\RefundedV1;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageHandler;

/**
 * Tells the customer their payment was refunded, once per payment. The event has no email, so the recipient comes
 * from the customers read model; an unknown customer (registered before this service consumed identity, and never
 * ordered) is logged and skipped: retrying or dead-lettering would not make the address appear.
 */
final readonly class RefundedHandler implements MessageHandler
{
    public function __construct(private QueueMail $queueMail) {}

    public function handle(Envelope $envelope): void
    {
        $event = RefundedV1::fromArray($envelope->data);

        $customer = Customer::query()->find($event->customerId);
        if ($customer === null) {
            Log::warning('Refund mail skipped: unknown customer', ['customerId' => $event->customerId, 'paymentId' => $event->paymentId]);

            return;
        }

        $this->queueMail->handle(
            messageId: $envelope->id,
            // Checkout refunds a payment once, in full; external refunds can be several partial ones per payment.
            dedupKey: $event->reason === 'external' ? $event->stripeRefundId : $event->paymentId,
            recipient: $customer->email,
            mail: new RefundMail($customer->name, new Amount($event->amount, $event->currency), $event->reason),
        );
    }
}
