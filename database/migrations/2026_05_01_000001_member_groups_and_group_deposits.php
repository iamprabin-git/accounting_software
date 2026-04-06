<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32)->nullable();
            $table->string('name', 180);
            $table->string('meeting_day', 16)->nullable();
            $table->string('status', 16)->default('active');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'member_groups_company_status_idx');
            $table->unique(['company_id', 'code'], 'member_groups_company_code_uq');
        });

        Schema::create('member_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_group_id')->constrained('member_groups')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->timestamps();

            $table->unique(['member_group_id', 'member_id'], 'member_group_members_uq');
            $table->index(['member_id', 'left_at'], 'member_group_members_member_left_idx');
        });

        Schema::create('group_deposit_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_group_id')->constrained('member_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('transaction_date');
            $table->foreignId('debit_chart_account_id')->constrained('chart_accounts')->restrictOnDelete();
            $table->string('reference', 64)->nullable();
            $table->string('memo')->nullable();
            $table->bigInteger('total_cents')->default(0);
            $table->unsignedInteger('line_count')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'member_group_id', 'transaction_date'], 'group_deposit_batches_co_group_date_idx');
        });

        Schema::create('group_deposit_batch_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_deposit_batch_id')->constrained('group_deposit_batches')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('financial_position_id')->constrained('financial_positions')->restrictOnDelete();
            $table->bigInteger('amount_cents');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();

            $table->index(['group_deposit_batch_id', 'member_id'], 'group_deposit_lines_batch_member_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_deposit_batch_lines');
        Schema::dropIfExists('group_deposit_batches');
        Schema::dropIfExists('member_group_members');
        Schema::dropIfExists('member_groups');
    }
};
