<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role', 32)->default('end_user')->after('company_id');
        });

        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $companyId = DB::table('companies')->insertGetId([
            'name' => 'Default organization',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('chart_accounts')->update(['company_id' => $companyId]);
        DB::table('journal_entries')->update(['company_id' => $companyId]);

        $ownerUserIds = collect()
            ->merge(DB::table('chart_accounts')->distinct()->pluck('user_id'))
            ->merge(DB::table('journal_entries')->distinct()->pluck('user_id'))
            ->unique()
            ->filter()
            ->values();

        DB::table('users')->update([
            'company_id' => null,
            'role' => 'end_user',
        ]);

        foreach ($ownerUserIds as $userId) {
            DB::table('users')->where('id', $userId)->update([
                'company_id' => $companyId,
                'role' => 'company',
            ]);
        }

        Schema::table('chart_accounts', function (Blueprint $table) {
            // MySQL keeps using the composite unique on `user_id` for the FK unless
            // a standalone index on `user_id` exists before we drop that unique.
            $table->index('user_id');
            $table->dropUnique(['user_id', 'code']);
            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->unique(['user_id', 'code']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'role']);
        });

        Schema::dropIfExists('companies');
    }
};
