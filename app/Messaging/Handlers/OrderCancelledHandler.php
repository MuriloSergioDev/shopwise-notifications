<?php

declare(strict_types=1);

namespace App\Messaging\Handlers;

use App\Actions\QueueMail;
use App\Mail\OrderCancelledMail;
use Shopwise\Contracts\Events\Orders\OrderCancelledV1;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageHandler;

/**
 * Tells the customer a placed order was cancelled, once per order. Without an order (orderId null: checkout gave up
 * before an order existed) the customer never got a confirmation, so there is nothing to retract.
 */
final readonly class OrderCancelledHandler implements MessageHandler
{
    public function __construct(private QueueMail $queueMail) {}

    public function handle(Envelope $envelope): void
    {
        $event = OrderCancelledV1::fromArray($envelope->data);
        if ($event->orderId === null || $event->number === null || $event->email === null) {
            return;
        }

        $this->queueMail->handle(
            messageId: $envelope->id,
            dedupKey: $event->orderId,
            recipient: $event->email,
            mail: new OrderCancelledMail($event->number, $event->reason),
        );
    }
}
