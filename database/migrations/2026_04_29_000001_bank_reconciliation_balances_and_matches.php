<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_reconciliation_batches', function (Blueprint $table) {
            $table->bigInteger('statement_opening_balance_cents')->nullable()->after('name');
            $table->bigInteger('statement_closing_balance_cents')->nullable()->after('statement_opening_balance_cents');
        });

        Schema::create('bank_statement_line_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_line_id')
                ->constrained('bank_statement_lines')
                ->cascadeOnDelete();
            $table->foreignId('journal_line_id')
                ->constrained('journal_lines')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique('journal_line_id', 'bank_stmt_line_match_jl_uq');
        });

        if (Schema::hasColumn('bank_statement_lines', 'journal_line_id')) {
            $rows = DB::table('bank_statement_lines')
                ->whereNotNull('journal_line_id')
                ->get(['id', 'journal_line_id']);

            $now = now();
            foreach ($rows as $row) {
                DB::table('bank_statement_line_matches')->insert([
                    'bank_statement_line_id' => $row->id,
                    'journal_line_id' => $row->journal_line_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            Schema::table('bank_statement_lines', function (Blueprint $table) {
                $table->dropForeign(['journal_line_id']);
            });

            Schema::table('bank_statement_lines', function (Blueprint $table) {
                $table->dropUnique(['journal_line_id']);
            });

            Schema::table('bank_statement_lines', function (Blueprint $table) {
                $table->dropColumn('journal_line_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_line_matches');

        Schema::table('bank_reconciliation_batches', function (Blueprint $table) {
            $table->dropColumn([
                'statement_opening_balance_cents',
                'statement_closing_balance_cents',
            ]);
        });

        if (! Schema::hasColumn('bank_statement_lines', 'journal_line_id')) {
            Schema::table('bank_statement_lines', function (Blueprint $table) {
                $table->foreignId('journal_line_id')->nullable()->constrained('journal_lines')->nullOnDelete();
            });
        }
    }
};
