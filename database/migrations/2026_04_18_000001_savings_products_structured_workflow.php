<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('product_code', 16);
            $table->string('name');
            $table->decimal('default_annual_interest_rate_percent', 10, 4)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'product_code'], 'savings_products_co_code_uq');
        });

        Schema::table('financial_positions', function (Blueprint $table) {
            $table->foreignId('savings_product_id')->nullable()->after('product_account_sequence')->constrained('savings_products')->nullOnDelete();
            $table->string('savings_workflow_status', 32)->nullable()->after('savings_product_id');
            $table->unsignedInteger('savings_product_account_sequence')->nullable()->after('savings_workflow_status');
        });
    }

    public function down(): void
    {
        Schema::table('financial_positions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('savings_product_id');
            $table->dropColumn(['savings_workflow_status', 'savings_product_account_sequence']);
        });

        Schema::dropIfExists('savings_products');
    }
};
