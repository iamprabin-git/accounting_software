<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('reversal_of_journal_entry_id')
                ->nullable()
                ->after('posted_number')
                ->constrained('journal_entries')
                ->nullOnDelete();
            $table->unique(['reversal_of_journal_entry_id'], 'journal_entries_reversal_of_unique');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropUnique('journal_entries_reversal_of_unique');
            $table->dropForeign(['reversal_of_journal_entry_id']);
            $table->dropColumn('reversal_of_journal_entry_id');
        });
    }
};
