<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_position_accruals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('accrual_year');
            $table->unsignedTinyInteger('accrual_month');
            $table->bigInteger('amount_cents');
            $table->string('kind', 32);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['financial_position_id', 'accrual_year', 'accrual_month', 'kind'],
                'fp_accruals_pos_yrmo_kind_uq',
            );
            $table->index(['company_id', 'accrual_year', 'accrual_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_position_accruals');
    }
};
