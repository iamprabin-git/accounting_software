<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->after('approved_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->dropForeign(['approved_by_user_id']);
            $table->dropColumn('approved_by_user_id');
        });
    }
};
