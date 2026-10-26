<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MailStatus;
use App\Models\MailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one logged mail and records the outcome. A mail already marked sent is skipped, so a job that
 * runs twice does not send twice (a crash between SMTP accepting it and the status update still can).
 */
#[Tries(4)]
final class SendMail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $mailLogId,
        public readonly Mailable $mail,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        $log = MailLog::query()->find($this->mailLogId);
        if ($log === null || $log->status === MailStatus::Sent) {
            return;
        }

        Mail::to($log->recipient)->send($this->mail);

        $log->update(['status' => MailStatus::Sent, 'sent_at' => now(), 'error' => null]);
    }

    public function failed(Throwable $exception): void
    {
        MailLog::query()
            ->whereKey($this->mailLogId)
            ->where('status', '!=', MailStatus::Sent)
            ->update(['status' => MailStatus::Failed, 'error' => mb_substr($exception->getMessage(), 0, 1000)]);
    }
}
