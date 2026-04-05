<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('product_code', 16);
            $table->string('name');
            $table->decimal('default_annual_interest_rate_percent', 10, 4)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'product_code'], 'loan_products_co_code_uq');
        });

        Schema::table('financial_positions', function (Blueprint $table) {
            $table->foreignId('loan_product_id')->nullable()->after('member_id')->constrained('loan_products')->nullOnDelete();
            $table->string('loan_workflow_status', 32)->nullable()->after('loan_product_id');
            $table->unsignedBigInteger('sanctioned_amount_cents')->nullable()->after('loan_workflow_status');
            $table->unsignedInteger('product_account_sequence')->nullable()->after('sanctioned_amount_cents');
        });

        Schema::table('financial_position_movements', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->after('memo')->constrained('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_position_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
        });

        Schema::table('financial_positions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loan_product_id');
            $table->dropColumn(['loan_workflow_status', 'sanctioned_amount_cents', 'product_account_sequence']);
        });

        Schema::dropIfExists('loan_products');
    }
};
