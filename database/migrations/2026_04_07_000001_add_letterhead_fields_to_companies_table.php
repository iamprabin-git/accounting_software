<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('address')->nullable()->after('name');
            $table->string('phone', 64)->nullable()->after('address');
            $table->string('logo_path', 2048)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['address', 'phone', 'logo_path']);
        });
    }
};
