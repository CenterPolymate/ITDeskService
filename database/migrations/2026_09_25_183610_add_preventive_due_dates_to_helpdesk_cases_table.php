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
            $table->date('preventive_measure_specific_due_date')->nullable();
            $table->date('preventive_measure_systemic_due_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('helpdesk_cases', function (Blueprint $table) {
            $table->dropColumn([
                'preventive_measure_specific_due_date',
                'preventive_measure_systemic_due_date',
            ]);
        });
    }
};
