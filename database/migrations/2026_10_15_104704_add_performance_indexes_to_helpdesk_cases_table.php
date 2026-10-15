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
            $table->index('status');
            $table->index('priority');
            $table->index('company');
            $table->index('escalated_to_team');
            $table->index('sla_due_at');
            $table->index('resolved_at');
            $table->index('requires_preventive_measure');
            $table->index(['status', 'priority']);
            $table->index(['company', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('helpdesk_cases', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['company']);
            $table->dropIndex(['escalated_to_team']);
            $table->dropIndex(['sla_due_at']);
            $table->dropIndex(['resolved_at']);
            $table->dropIndex(['requires_preventive_measure']);
            $table->dropIndex(['status', 'priority']);
            $table->dropIndex(['company', 'created_at']);
        });
    }
};
