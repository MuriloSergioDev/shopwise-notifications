<?php

declare(strict_types=1);

namespace App\Messaging\Handlers;

use App\Actions\QueueMail;
use App\Actions\RecordCustomer;
use App\Mail\WelcomeMail;
use Shopwise\Contracts\Events\Identity\CustomerRegisteredV1;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageHandler;

/**
 * Remembers the customer's contact data for later mails and welcomes them.
 */
final readonly class CustomerRegisteredHandler implements MessageHandler
{
    public function __construct(
        private RecordCustomer $recordCustomer,
        private QueueMail $queueMail,
    ) {}

    public function handle(Envelope $envelope): void
    {
        $event = CustomerRegisteredV1::fromArray($envelope->data);

        $this->recordCustomer->handle($event->customerId, $event->email, $event->name);
        $this->queueMail->handle(
            messageId: $envelope->id,
            dedupKey: $event->customerId,
            recipient: $event->email,
            mail: new WelcomeMail($event->name),
        );
    }
}
