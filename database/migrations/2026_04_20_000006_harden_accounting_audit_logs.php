<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_audit_logs', function (Blueprint $table) {
            $table->string('actor_ip', 64)->nullable()->after('user_id');
            $table->string('actor_user_agent', 500)->nullable()->after('actor_ip');
            $table->string('previous_event_hash', 64)->nullable()->after('metadata');
            $table->string('event_hash', 64)->nullable()->after('previous_event_hash');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_audit_logs', function (Blueprint $table) {
            $table->dropColumn([
                'actor_ip',
                'actor_user_agent',
                'previous_event_hash',
                'event_hash',
            ]);
        });
    }
};
