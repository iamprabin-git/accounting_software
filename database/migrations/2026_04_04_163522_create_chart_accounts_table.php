<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->string('type', 32);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_accounts');
    }
};
