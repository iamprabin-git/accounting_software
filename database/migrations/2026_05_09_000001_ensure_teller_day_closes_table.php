<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair migration: phase-2 may have been recorded without this table (partial run / manual changes).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teller_day_closes')) {
            return;
        }

        Schema::create('teller_day_closes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('close_date');
            $table->bigInteger('opening_cash_cents')->default(0);
            $table->bigInteger('counted_cash_cents');
            $table->bigInteger('expected_cash_cents')->nullable();
            $table->string('memo')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id', 'close_date'], 'teller_day_closes_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teller_day_closes');
    }
};
