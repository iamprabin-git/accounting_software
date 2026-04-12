<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_working_day_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->timestamps();

            $table->unique(['company_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_working_day_overrides');
    }
};
