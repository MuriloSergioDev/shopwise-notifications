<?php

declare(strict_types=1);

namespace App\Enums;

enum MailStatus: string
{
    /** Logged and waiting for the queue worker. */
    case Queued = 'queued';

    /** Handed to the mailer (SMTP accepted it). */
    case Sent = 'sent';

    /** Every attempt failed; `error` says why. */
    case Failed = 'failed';
}
