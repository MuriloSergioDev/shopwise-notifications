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
 * Logs a mail caused by an event and queues it. `mail_log` is unique per (template, dedup key), where the dedup key
 * is the business id the mail is about (customer, order, payment): a redelivered event, or an outcome its producer
 * re-published under a new envelope id, finds the row and queues nothing. The job is dispatched after commit, so no
 * mail goes out for an event whose handling rolled back.
 */
final readonly class QueueMail
{
    /**
     * @return bool whether the mail was queued (false: it was already queued for this dedup key)
     */
    public function handle(string $messageId, string $dedupKey, string $recipient, Mailable&TemplatedMail $mail): bool
    {
        $id = (string) Str::ulid();
        $now = now();

        $inserted = MailLog::query()->insertOrIgnore([
            'id' => $id,
            'message_id' => $messageId,
            'dedup_key' => $dedupKey,
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
