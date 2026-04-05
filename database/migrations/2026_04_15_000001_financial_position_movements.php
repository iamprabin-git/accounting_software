<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_position_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32);
            $table->bigInteger('amount_cents');
            $table->unsignedBigInteger('balance_after_cents');
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->index(['financial_position_id', 'created_at'], 'fpm_pos_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_position_movements');
    }
};
