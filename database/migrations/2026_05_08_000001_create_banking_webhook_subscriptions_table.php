<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banking_webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('url', 2048);
            $table->text('secret'); // encrypted app secret for HMAC
            $table->json('events');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active'], 'bwh_sub_co_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banking_webhook_subscriptions');
    }
};
