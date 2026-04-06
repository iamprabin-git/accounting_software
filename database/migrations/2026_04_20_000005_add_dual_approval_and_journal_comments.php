<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('dual_approval_threshold_cents')
                ->nullable()
                ->after('next_journal_posted_number');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('first_approved_by_user_id')
                ->nullable()
                ->after('approved_by_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('first_approved_at')->nullable()->after('first_approved_by_user_id');
        });

        Schema::create('journal_approval_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 24);
            $table->text('comment');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['journal_entry_id', 'created_at'], 'journal_approval_comments_journal_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_approval_comments');

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['first_approved_by_user_id']);
            $table->dropColumn(['first_approved_by_user_id', 'first_approved_at']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('dual_approval_threshold_cents');
        });
    }
};
