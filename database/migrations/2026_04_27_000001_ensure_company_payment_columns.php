<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds company payment columns if they are still missing (e.g. migration batch out of sync).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'bank_payment_details')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->text('bank_payment_details')->nullable();
            });
        }

        if (! Schema::hasColumn('companies', 'payment_qr_path')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('payment_qr_path', 512)->nullable();
            });
        }

        if (! Schema::hasColumn('companies', 'portal_show_payment_details')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->boolean('portal_show_payment_details')->default(true);
            });
        }
    }

    public function down(): void
    {
        //
    }
};
