<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing values
        DB::table('companies')->where('sla_type', '8x5')->update(['sla_type' => '8x5x4']);
        DB::table('companies')->where('sla_type', '24x7')->update(['sla_type' => '24x7x4']);

        // Change column default
        Schema::table('companies', function (Blueprint $table) {
            $table->string('sla_type')->default('8x5x4')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('companies')->where('sla_type', '8x5x4')->update(['sla_type' => '8x5']);
        DB::table('companies')->where('sla_type', '24x7x4')->update(['sla_type' => '24x7']);

        Schema::table('companies', function (Blueprint $table) {
            $table->string('sla_type')->default('8x5')->change();
        });
    }
};
