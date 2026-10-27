<?php

declare(strict_types=1);

namespace App\Messaging\Handlers;

use App\Actions\QueueMail;
use App\Mail\OrderShippedMail;
use Shopwise\Contracts\Events\Orders\OrderShippedV1;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageHandler;

/**
 * Mails the tracking details once per order.
 */
final readonly class OrderShippedHandler implements MessageHandler
{
    public function __construct(private QueueMail $queueMail) {}

    public function handle(Envelope $envelope): void
    {
        $event = OrderShippedV1::fromArray($envelope->data);

        $this->queueMail->handle(
            messageId: $envelope->id,
            dedupKey: $event->orderId,
            recipient: $event->email,
            mail: new OrderShippedMail($event->number, $event->carrier, $event->trackingNumber),
        );
    }
}
