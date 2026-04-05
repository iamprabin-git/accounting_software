<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('status', 24)->default('draft')->after('transaction_date');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->foreignId('approved_by_user_id')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
        });

        DB::table('journal_entries')->update([
            'status' => 'approved',
            'approved_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['approved_by_user_id']);
            $table->dropColumn(['status', 'submitted_at', 'approved_by_user_id', 'approved_at']);
        });
    }
};
