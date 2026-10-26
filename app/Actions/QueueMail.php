<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MailStatus;
use App\Jobs\SendMail;
use App\Mail\TemplatedMail;
use App\Models\MailLog;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Str;

/**
 * Logs a mail caused by an event and queues it. `mail_log` is unique per (message id, template), so a
 * redelivered event finds its row and queues nothing. The job is dispatched after commit: no mail goes
 * out for an event whose handling rolled back.
 */
final readonly class QueueMail
{
    /**
     * @return bool whether the mail was queued (false: this event already produced it)
     */
    public function handle(string $messageId, string $recipient, Mailable&TemplatedMail $mail): bool
    {
        $id = (string) Str::ulid();
        $now = now();

        $inserted = MailLog::query()->insertOrIgnore([
            'id' => $id,
            'message_id' => $messageId,
            'template' => $mail->template(),
            'recipient' => $recipient,
            'status' => MailStatus::Queued->value,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($inserted === 0) {
            return false;
        }

        SendMail::dispatch($id, $mail)->afterCommit();

        return true;
    }
}
