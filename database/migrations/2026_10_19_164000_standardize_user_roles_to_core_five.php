<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Standardizes all user roles to the 5 Core ITIL 4 Standard Roles:
     * 1. administrator (System Administrator)
     * 2. manager (IT Service Manager - Tier 3)
     * 3. technician (Technical Specialist - Tier 2)
     * 4. helpdesk (Service Desk Agent - Tier 1)
     * 5. user (End User / Requester)
     */
    public function up(): void
    {
        // 1. Ensure team memberships exist for legacy roles before migrating role column
        $teamMapping = [
            'team_hardware' => 'Hardware',
            'team_network' => 'Network',
            'team_software' => 'Software',
        ];

        foreach ($teamMapping as $legacyRole => $teamName) {
            $team = DB::table('teams')->where('name', $teamName)->first();
            if ($team) {
                $members = DB::table('users')->where('role', $legacyRole)->get();
                foreach ($members as $member) {
                    $exists = DB::table('team_user')
                        ->where('team_id', $team->id)
                        ->where('user_id', $member->id)
                        ->exists();

                    if (! $exists) {
                        DB::table('team_user')->insert([
                            'team_id' => $team->id,
                            'user_id' => $member->id,
                            'role_in_team' => 'member',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // 2. Map Tier 2 legacy roles to 'technician'
        DB::table('users')
            ->whereIn('role', ['team_hardware', 'team_network', 'team_software'])
            ->update([
                'role' => 'technician',
                'updated_at' => now(),
            ]);

        // 3. Map Tier 3 legacy roles to 'manager'
        DB::table('users')
            ->whereIn('role', ['manager_software', 'manager_network_hardware'])
            ->update([
                'role' => 'manager',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback is a no-op as 5 standard roles remain valid forward
    }
};
