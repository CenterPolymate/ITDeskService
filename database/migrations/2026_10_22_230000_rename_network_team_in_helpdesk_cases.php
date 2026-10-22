<?php

use App\Models\HelpdeskCase;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update team in teams table with code 'NET' or name 'Network' to 'Network production'
        $netTeam = Team::where('code', 'NET')->orWhere('name', 'Network')->first();
        if ($netTeam) {
            $netTeam->update(['name' => 'Network production']);
        }

        // 2. Update all helpdesk cases where escalated_to_team is 'Network' or 'team_network' to 'Network production'
        HelpdeskCase::where('escalated_to_team', 'Network')
            ->orWhere('escalated_to_team', 'team_network')
            ->update(['escalated_to_team' => 'Network production']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        HelpdeskCase::where('escalated_to_team', 'Network production')
            ->update(['escalated_to_team' => 'Network']);

        $netTeam = Team::where('code', 'NET')->first();
        if ($netTeam) {
            $netTeam->update(['name' => 'Network']);
        }
    }
};
