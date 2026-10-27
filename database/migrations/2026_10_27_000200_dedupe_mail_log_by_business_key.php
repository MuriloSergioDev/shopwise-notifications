<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Producers re-publish an outcome with a new envelope id when a command is repeated (ADR-0004), so the envelope id
 * cannot be the dedup key: a mail is now unique per (template, dedup_key), e.g. ('order-confirmation', orderId).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_log', function (Blueprint $table): void {
            $table->string('dedup_key', 64)->nullable()->after('message_id');
        });

        // Only welcome mails existed so far; their envelope id stands in for the customer id they lacked.
        DB::table('mail_log')->update(['dedup_key' => DB::raw('message_id')]);

        Schema::table('mail_log', function (Blueprint $table): void {
            $table->string('dedup_key', 64)->nullable(false)->change();
            $table->dropUnique(['message_id', 'template']);
            $table->unique(['template', 'dedup_key']);
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::table('mail_log', function (Blueprint $table): void {
            $table->dropUnique(['template', 'dedup_key']);
            $table->dropIndex(['message_id']);
            $table->unique(['message_id', 'template']);
            $table->dropColumn('dedup_key');
        });
    }
};
