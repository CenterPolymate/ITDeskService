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
            $table->string('actual_user_name')->nullable()->after('requester_phone');
            $table->string('machine_name')->nullable()->after('actual_user_name');
            $table->string('machine_code')->nullable()->after('machine_name');
            $table->boolean('is_machine_stopped')->nullable()->after('machine_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('helpdesk_cases', function (Blueprint $table) {
            $table->dropColumn(['actual_user_name', 'machine_name', 'machine_code', 'is_machine_stopped']);
        });
    }
};
