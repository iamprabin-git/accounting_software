<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        Schema::table('companies', function (Blueprint $table) {
            $drop = array_values(array_filter([
                Schema::hasColumn('companies', 'bank_payment_details') ? 'bank_payment_details' : null,
                Schema::hasColumn('companies', 'payment_qr_path') ? 'payment_qr_path' : null,
                Schema::hasColumn('companies', 'portal_show_payment_details') ? 'portal_show_payment_details' : null,
            ]));
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
