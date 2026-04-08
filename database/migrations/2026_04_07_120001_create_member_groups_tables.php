<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
        });

        Schema::create('member_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_group_id')->constrained('member_groups')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['member_group_id', 'member_id']);
            $table->index(['member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_group_members');
        Schema::dropIfExists('member_groups');
    }
};
