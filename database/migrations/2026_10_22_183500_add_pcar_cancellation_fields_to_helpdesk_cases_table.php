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
            $table->text('pcar_cancellation_reason')->nullable()->after('pcar_closed_by');
            $table->timestamp('pcar_cancelled_at')->nullable()->after('pcar_cancellation_reason');
            $table->foreignId('pcar_cancelled_by')->nullable()->constrained('users')->nullOnDelete()->after('pcar_cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('helpdesk_cases', function (Blueprint $table) {
            $table->dropForeign(['pcar_cancelled_by']);
            $table->dropColumn([
                'pcar_cancellation_reason',
                'pcar_cancelled_at',
                'pcar_cancelled_by',
            ]);
        });
    }
};
