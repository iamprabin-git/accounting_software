<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teller_day_closes')) {
            Schema::table('teller_day_closes', function (Blueprint $table) {
                if (! Schema::hasColumn('teller_day_closes', 'day_status')) {
                    $table->string('day_status', 20)->default('closed')->after('close_date');
                }
                if (! Schema::hasColumn('teller_day_closes', 'started_at')) {
                    $table->timestamp('started_at')->nullable()->after('memo');
                }
                if (! Schema::hasColumn('teller_day_closes', 'ended_at')) {
                    $table->timestamp('ended_at')->nullable()->after('started_at');
                }
                if (! Schema::hasColumn('teller_day_closes', 'vault_opening_cash_cents')) {
                    $table->bigInteger('vault_opening_cash_cents')->nullable()->after('opening_cash_cents');
                }
                if (! Schema::hasColumn('teller_day_closes', 'cash_received_cents')) {
                    $table->bigInteger('cash_received_cents')->default(0)->after('vault_opening_cash_cents');
                }
                if (! Schema::hasColumn('teller_day_closes', 'system_cash_cents')) {
                    $table->bigInteger('system_cash_cents')->nullable()->after('expected_cash_cents');
                }
                if (! Schema::hasColumn('teller_day_closes', 'vault_returned_cash_cents')) {
                    $table->bigInteger('vault_returned_cash_cents')->nullable()->after('system_cash_cents');
                }
                if (! Schema::hasColumn('teller_day_closes', 'closing_error_cents')) {
                    $table->bigInteger('closing_error_cents')->default(0)->after('vault_returned_cash_cents');
                }
            });
        }

        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (! Schema::hasColumn('companies', 'last_period_close_type')) {
                    $table->string('last_period_close_type', 40)->nullable()->after('journal_lock_reason');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teller_day_closes')) {
            Schema::table('teller_day_closes', function (Blueprint $table) {
                foreach ([
                    'day_status',
                    'started_at',
                    'ended_at',
                    'vault_opening_cash_cents',
                    'cash_received_cents',
                    'system_cash_cents',
                    'vault_returned_cash_cents',
                    'closing_error_cents',
                ] as $col) {
                    if (Schema::hasColumn('teller_day_closes', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'last_period_close_type')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('last_period_close_type');
            });
        }
    }
};

