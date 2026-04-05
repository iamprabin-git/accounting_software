<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'name']);
        });

        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_account_id')->nullable()->constrained('crm_accounts')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('job_title')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'last_name', 'first_name']);
        });

        Schema::create('crm_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_account_id')->nullable()->constrained('crm_accounts')->nullOnDelete();
            $table->foreignId('crm_contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->string('name');
            $table->string('stage', 32)->default('lead');
            $table->unsignedBigInteger('amount_cents')->nullable();
            $table->unsignedTinyInteger('probability')->nullable();
            $table->date('expected_close_date')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'stage']);
        });

        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'due_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_opportunities');
        Schema::dropIfExists('crm_contacts');
        Schema::dropIfExists('crm_accounts');
    }
};
