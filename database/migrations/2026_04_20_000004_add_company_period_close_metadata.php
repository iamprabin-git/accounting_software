<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('journal_lock_reason')->nullable()->after('journal_lock_date');
            $table->foreignId('journal_lock_updated_by_user_id')
                ->nullable()
                ->after('journal_lock_reason')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('journal_lock_updated_at')->nullable()->after('journal_lock_updated_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['journal_lock_updated_by_user_id']);
            $table->dropColumn([
                'journal_lock_reason',
                'journal_lock_updated_by_user_id',
                'journal_lock_updated_at',
            ]);
        });
    }
};
