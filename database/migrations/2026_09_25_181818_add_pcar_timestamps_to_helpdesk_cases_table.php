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
            $table->timestamp('pcar_opened_at')->nullable();
            $table->foreignId('pcar_opened_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('pcar_analyzed_at')->nullable();
            $table->foreignId('pcar_analyzed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('pcar_closed_at')->nullable();
            $table->foreignId('pcar_closed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('helpdesk_cases', function (Blueprint $table) {
            $table->dropForeign(['pcar_opened_by']);
            $table->dropForeign(['pcar_analyzed_by']);
            $table->dropForeign(['pcar_closed_by']);

            $table->dropColumn([
                'pcar_opened_at',
                'pcar_opened_by',
                'pcar_analyzed_at',
                'pcar_analyzed_by',
                'pcar_closed_at',
                'pcar_closed_by',
            ]);
        });
    }
};
