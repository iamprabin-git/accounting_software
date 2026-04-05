<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_positions', function (Blueprint $table) {
            $table->string('account_number', 32)->nullable();
        });

        Schema::table('financial_positions', function (Blueprint $table) {
            $table->unique(['company_id', 'account_number'], 'fin_pos_co_acct_uq');
        });

        $rows = DB::table('financial_positions')
            ->whereIn('category', ['loan', 'savings'])
            ->whereNull('account_number')
            ->orderBy('id')
            ->get(['id', 'category']);

        foreach ($rows as $row) {
            $prefix = $row->category === 'loan' ? 'LN' : 'SV';
            DB::table('financial_positions')
                ->where('id', $row->id)
                ->update(['account_number' => $prefix.'-'.$row->id]);
        }
    }

    public function down(): void
    {
        Schema::table('financial_positions', function (Blueprint $table) {
            $table->dropUnique('fin_pos_co_acct_uq');
            $table->dropColumn('account_number');
        });
    }
};
