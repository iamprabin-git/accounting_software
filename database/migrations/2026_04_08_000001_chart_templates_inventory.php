<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_account_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32);
            $table->string('name');
            $table->string('type', 32);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('code');
        });

        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->foreignId('chart_account_template_id')
                ->nullable()
                ->after('company_id')
                ->constrained('chart_account_templates')
                ->nullOnDelete();
            $table->string('approval_status', 32)->default('approved')->after('description');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->foreignId('approved_by_admin_id')
                ->nullable()
                ->after('approved_at')
                ->constrained('admins')
                ->nullOnDelete();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('inventory_chart_account_id')
                ->nullable()
                ->after('logo_path')
                ->constrained('chart_accounts')
                ->nullOnDelete();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64)->nullable();
            $table->string('name');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->unsignedBigInteger('unit_cost_cents')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['inventory_chart_account_id']);
            $table->dropColumn('inventory_chart_account_id');
        });

        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->dropForeign(['chart_account_template_id']);
            $table->dropForeign(['approved_by_admin_id']);
            $table->dropColumn([
                'chart_account_template_id',
                'approval_status',
                'approved_at',
                'approved_by_admin_id',
            ]);
        });

        Schema::dropIfExists('chart_account_templates');
    }
};
