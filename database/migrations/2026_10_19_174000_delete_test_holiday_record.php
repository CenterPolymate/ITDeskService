<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('holidays')
            ->where('date', '2026-08-19')
            ->orWhere('name', 'วันนนท์ไงจะใครล่ะ')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse action needed
    }
};
