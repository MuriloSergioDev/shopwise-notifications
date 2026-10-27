<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MailStatus;
use Database\Factories\MailLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One email this service decided to send, keyed by the event that caused it.
 *
 * @property string $id
 * @property string $message_id
 * @property string $dedup_key
 * @property string $template
 * @property string $recipient
 * @property MailStatus $status
 * @property string|null $error
 * @property Carbon|null $sent_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['message_id', 'dedup_key', 'template', 'recipient', 'status', 'error', 'sent_at'])]
#[Table('mail_log')]
final class MailLog extends Model
{
    /** @use HasFactory<MailLogFactory> */
    use HasFactory;

    use HasUlids;

    protected function casts(): array
    {
        return [
            'status' => MailStatus::class,
            'sent_at' => 'datetime',
        ];
    }
}
