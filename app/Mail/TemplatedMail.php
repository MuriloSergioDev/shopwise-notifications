<?php

declare(strict_types=1);

namespace App\Mail;

/**
 * A mailable this service sends in reaction to an event. The template name is what `mail_log` records,
 * so it must stay stable once mails have been sent with it.
 */
interface TemplatedMail
{
    public function template(): string;
}
