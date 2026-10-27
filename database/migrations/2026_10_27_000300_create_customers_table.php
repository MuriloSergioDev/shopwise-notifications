<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Read model of identity's customers: where to mail events that only carry a customer id.
        Schema::create('customers', function (Blueprint $table): void {
            $table->string('customer_id', 26)->primary();
            $table->string('email');
            $table->string('name', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
