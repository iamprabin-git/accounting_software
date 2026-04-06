<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('group_loan_collection_batches')) {
            Schema::create('group_loan_collection_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('member_group_id')->constrained('member_groups')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('transaction_date');
                $table->foreignId('debit_chart_account_id')->constrained('chart_accounts')->restrictOnDelete();

                $table->unsignedBigInteger('interest_revenue_chart_account_id')->nullable();
                $table->foreign('interest_revenue_chart_account_id', 'glc_b_int_rev_fk')
                    ->references('id')->on('chart_accounts')->nullOnDelete();

                $table->unsignedBigInteger('penalty_credit_chart_account_id')->nullable();
                $table->foreign('penalty_credit_chart_account_id', 'glc_b_pen_cred_fk')
                    ->references('id')->on('chart_accounts')->nullOnDelete();

                $table->string('reference', 64)->nullable();
                $table->string('memo')->nullable();
                $table->bigInteger('total_principal_cents')->default(0);
                $table->bigInteger('total_interest_cents')->default(0);
                $table->bigInteger('total_penalty_cents')->default(0);
                $table->unsignedInteger('line_count')->default(0);
                $table->timestamps();

                $table->index(['company_id', 'member_group_id', 'transaction_date'], 'glc_batches_co_grp_dt_idx');
            });
        }

        if (! Schema::hasTable('group_loan_collection_batch_lines')) {
            Schema::create('group_loan_collection_batch_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('group_loan_collection_batch_id');
                $table->foreign('group_loan_collection_batch_id', 'glc_ln_batch_fk')
                    ->references('id')->on('group_loan_collection_batches')->cascadeOnDelete();

                $table->foreignId('member_id')->constrained('members')->restrictOnDelete();

                $table->unsignedBigInteger('financial_position_id');
                $table->foreign('financial_position_id', 'glc_ln_finpos_fk')
                    ->references('id')->on('financial_positions')->restrictOnDelete();

                $table->bigInteger('principal_cents')->default(0);
                $table->bigInteger('interest_cents')->default(0);
                $table->bigInteger('penalty_cents')->default(0);

                $table->unsignedBigInteger('principal_journal_entry_id')->nullable();
                $table->foreign('principal_journal_entry_id', 'glc_ln_pr_je_fk')
                    ->references('id')->on('journal_entries')->nullOnDelete();

                $table->unsignedBigInteger('interest_journal_entry_id')->nullable();
                $table->foreign('interest_journal_entry_id', 'glc_ln_int_je_fk')
                    ->references('id')->on('journal_entries')->nullOnDelete();

                $table->unsignedBigInteger('penalty_journal_entry_id')->nullable();
                $table->foreign('penalty_journal_entry_id', 'glc_ln_pen_je_fk')
                    ->references('id')->on('journal_entries')->nullOnDelete();

                $table->timestamps();

                $table->index(['group_loan_collection_batch_id', 'member_id'], 'glc_lines_batch_mbr_idx');
            });
        }

        if (! Schema::hasTable('teller_day_closes')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('teller_day_closes');
        Schema::dropIfExists('group_loan_collection_batch_lines');
        Schema::dropIfExists('group_loan_collection_batches');
    }
};
