<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedInteger('member_number')->nullable()->after('company_id');
        });

        $companyIds = DB::table('members')->distinct()->pluck('company_id');
        foreach ($companyIds as $companyId) {
            $ids = DB::table('members')
                ->where('company_id', $companyId)
                ->orderBy('id')
                ->pluck('id');
            $n = 1;
            foreach ($ids as $id) {
                DB::table('members')->where('id', $id)->update(['member_number' => $n]);
                $n++;
            }
        }

        Schema::table('members', function (Blueprint $table) {
            $table->unique(['company_id', 'member_number'], 'members_co_member_number_uq');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('member_id')
                ->nullable()
                ->after('company_id')
                ->constrained('members')
                ->nullOnDelete();
            $table->string('finance_category', 32)->nullable()->after('member_id');
            $table->index(['company_id', 'member_id', 'finance_category'], 'je_co_member_finance_idx');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex('je_co_member_finance_idx');
            $table->dropForeign(['member_id']);
            $table->dropColumn(['member_id', 'finance_category']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique('members_co_member_number_uq');
            $table->dropColumn('member_number');
        });
    }
};
