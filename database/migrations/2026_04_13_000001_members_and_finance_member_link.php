<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('reference_code', 32)->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 16)->default('pending');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'members_co_status_idx');
            $table->unique(['company_id', 'reference_code'], 'members_co_ref_uq');
        });

        Schema::table('financial_positions', function (Blueprint $table) {
            $table->foreignId('member_id')
                ->nullable()
                ->after('company_id')
                ->constrained('members')
                ->restrictOnDelete();
            $table->index('member_id', 'fin_pos_member_idx');
        });
    }

    public function down(): void
    {
        Schema::table('financial_positions', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropIndex('fin_pos_member_idx');
            $table->dropColumn('member_id');
        });

        Schema::dropIfExists('members');
    }
};
