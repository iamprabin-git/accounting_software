<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->string('title')->nullable();
            $table->text('body');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('status', 24)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
