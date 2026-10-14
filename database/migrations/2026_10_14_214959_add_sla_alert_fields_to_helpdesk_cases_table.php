<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('helpdesk_cases', function (Blueprint $table) {
            $table->timestamp('sla_warning_sent_at')->nullable()->after('sla_due_at');
            $table->timestamp('sla_breached_sent_at')->nullable()->after('sla_warning_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('helpdesk_cases', function (Blueprint $table) {
            $table->dropColumn(['sla_warning_sent_at', 'sla_breached_sent_at']);
        });
    }
};
