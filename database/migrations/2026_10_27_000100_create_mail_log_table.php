<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_log', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            // Envelope id of the event that caused the mail.
            $table->string('message_id', 64);
            $table->string('template', 64);
            $table->string('recipient');
            $table->string('status', 10);
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // One mail per template per event, however often the event is delivered.
            $table->unique(['message_id', 'template']);
            $table->index('recipient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_log');
    }
};
