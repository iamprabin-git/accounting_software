<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('portal_approved_at')->nullable()->after('is_active');
            $table->foreignId('portal_approved_by_user_id')->nullable()->after('portal_approved_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('portal_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('end_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['company_id', 'end_user_id', 'created_at'], 'portal_messages_co_user_created_idx');
        });

        if (Schema::hasTable('users')) {
            DB::table('users')
                ->where('role', 'end_user')
                ->whereNull('portal_approved_at')
                ->update(['portal_approved_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_messages');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('portal_approved_by_user_id');
            $table->dropColumn('portal_approved_at');
        });
    }
};
