<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->date('journal_lock_date')->nullable()->after('inventory_chart_account_id');
            $table->unsignedBigInteger('next_journal_posted_number')->default(1)->after('journal_lock_date');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('posted_number')->nullable()->after('approved_at');
            $table->unique(['company_id', 'posted_number'], 'journal_entries_company_posted_number_uq');
        });

        $companyIds = DB::table('companies')->pluck('id');

        foreach ($companyIds as $companyId) {
            $approvedEntries = DB::table('journal_entries')
                ->where('company_id', $companyId)
                ->where('status', 'approved')
                ->whereNull('posted_number')
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get(['id']);

            $next = 1;
            foreach ($approvedEntries as $entry) {
                DB::table('journal_entries')
                    ->where('id', $entry->id)
                    ->update(['posted_number' => $next]);
                $next++;
            }

            $maxPosted = (int) (DB::table('journal_entries')
                ->where('company_id', $companyId)
                ->max('posted_number') ?? 0);

            DB::table('companies')
                ->where('id', $companyId)
                ->update(['next_journal_posted_number' => $maxPosted + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropUnique('journal_entries_company_posted_number_uq');
            $table->dropColumn('posted_number');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['journal_lock_date', 'next_journal_posted_number']);
        });
    }
};
