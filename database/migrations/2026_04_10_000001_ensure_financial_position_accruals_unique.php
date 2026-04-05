<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * If the accruals table was left without a unique index (e.g. MySQL 64-char identifier limit on an auto-generated name), add the short-named constraint.
     */
    public function up(): void
    {
        if (! Schema::hasTable('financial_position_accruals')) {
            return;
        }

        $connection = Schema::getConnection();
        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        $rows = $connection->select(
            "SHOW INDEX FROM financial_position_accruals WHERE Key_name = 'fp_accruals_pos_yrmo_kind_uq'",
        );

        if (count($rows) > 0) {
            return;
        }

        Schema::table('financial_position_accruals', function (Blueprint $table) {
            $table->unique(
                ['financial_position_id', 'accrual_year', 'accrual_month', 'kind'],
                'fp_accruals_pos_yrmo_kind_uq',
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('financial_position_accruals')) {
            return;
        }

        Schema::table('financial_position_accruals', function (Blueprint $table) {
            $table->dropUnique('fp_accruals_pos_yrmo_kind_uq');
        });
    }
};
