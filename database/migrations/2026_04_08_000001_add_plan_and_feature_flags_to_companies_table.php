<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('plan', 32)
                ->default('enterprise')
                ->after('name');
            $table->boolean('feature_inventory_enabled')
                ->default(true)
                ->after('plan');
            $table->boolean('feature_members_enabled')
                ->default(true)
                ->after('feature_inventory_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'plan',
                'feature_inventory_enabled',
                'feature_members_enabled',
            ]);
        });
    }
};
