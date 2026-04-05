<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debtors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('reference', 64)->nullable();
            $table->bigInteger('balance_cents')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('creditors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('reference', 64)->nullable();
            $table->bigInteger('balance_cents')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('category', 32);
            $table->string('title');
            $table->bigInteger('principal_cents')->default(0);
            $table->decimal('annual_interest_rate_percent', 10, 4)->default(0);
            $table->date('start_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_positions');
        Schema::dropIfExists('creditors');
        Schema::dropIfExists('debtors');
    }
};
