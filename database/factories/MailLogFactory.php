<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MailStatus;
use App\Models\MailLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MailLog>
 */
final class MailLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'message_id' => (string) Str::ulid(),
            'dedup_key' => (string) Str::ulid(),
            'template' => 'welcome',
            'recipient' => fake()->safeEmail(),
            'status' => MailStatus::Queued,
        ];
    }

    public function sent(): self
    {
        return $this->state(['status' => MailStatus::Sent, 'sent_at' => now()]);
    }
}
