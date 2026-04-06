<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_reconciliation_batches')) {
            Schema::create('bank_reconciliation_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('chart_account_id')->constrained('chart_accounts')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name', 160)->nullable();
                $table->timestamps();

                $table->index(
                    ['company_id', 'chart_account_id'],
                    'bank_recon_batches_co_chart_idx',
                );
            });
        }

        if (! Schema::hasTable('bank_statement_lines')) {
            Schema::create('bank_statement_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_reconciliation_batch_id')
                    ->constrained('bank_reconciliation_batches')
                    ->cascadeOnDelete();
                $table->date('transaction_date');
                $table->bigInteger('amount_cents');
                $table->string('description', 500)->nullable();
                $table->string('external_reference', 120)->nullable();
                $table->foreignId('journal_line_id')->nullable()->constrained('journal_lines')->nullOnDelete();
                $table->timestamp('reconciled_at')->nullable();
                $table->timestamps();

                $table->unique('journal_line_id');
                $table->index(
                    ['bank_reconciliation_batch_id', 'transaction_date'],
                    'bank_stmt_lines_batch_date_idx',
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_reconciliation_batches');
    }
};
