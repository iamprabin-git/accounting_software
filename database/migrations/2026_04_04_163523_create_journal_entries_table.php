<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 64)->nullable();
            $table->string('memo')->nullable();
            $table->date('transaction_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
