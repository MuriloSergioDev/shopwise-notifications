<?php

declare(strict_types=1);

namespace App\Messaging\Handlers;

use App\Actions\QueueMail;
use App\Actions\RecordCustomer;
use App\Data\PlacedOrder;
use App\Mail\OrderConfirmationMail;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageHandler;

/**
 * Confirms a placed order by email, once per order (orders re-publishes order-placed when place-order is repeated).
 * Also refreshes the customer's email, so customers registered before this service existed can get refund mails.
 */
final readonly class OrderPlacedHandler implements MessageHandler
{
    public function __construct(
        private RecordCustomer $recordCustomer,
        private QueueMail $queueMail,
    ) {}

    public function handle(Envelope $envelope): void
    {
        $order = PlacedOrder::fromEventData($envelope->data);

        $this->recordCustomer->handle($order->customerId, $order->email);
        $this->queueMail->handle(
            messageId: $envelope->id,
            dedupKey: $order->orderId,
            recipient: $order->email,
            mail: new OrderConfirmationMail($order),
        );
    }
}
