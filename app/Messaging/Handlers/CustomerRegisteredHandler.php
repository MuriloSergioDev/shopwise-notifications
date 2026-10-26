<?php

declare(strict_types=1);

namespace App\Messaging\Handlers;

use App\Actions\QueueMail;
use App\Mail\WelcomeMail;
use Shopwise\Contracts\Events\Identity\CustomerRegisteredV1;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageHandler;

/**
 * Welcomes a newly registered customer by email.
 */
final readonly class CustomerRegisteredHandler implements MessageHandler
{
    public function __construct(private QueueMail $queueMail) {}

    public function handle(Envelope $envelope): void
    {
        $event = CustomerRegisteredV1::fromArray($envelope->data);

        $this->queueMail->handle($envelope->id, $event->email, new WelcomeMail($event->name));
    }
}
