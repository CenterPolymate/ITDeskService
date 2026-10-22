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
        // Sync any legacy team names to their current team names based on code/name mapping
        $hwTeam = Team::where('code', 'HW')->first();
        if ($hwTeam && $hwTeam->name !== 'Hardware') {
            HelpdeskCase::where('escalated_to_team', 'Hardware')->update(['escalated_to_team' => $hwTeam->name]);
        }

        $netTeam = Team::where('code', 'NET')->first();
        if ($netTeam && $netTeam->name !== 'Network') {
            HelpdeskCase::where('escalated_to_team', 'Network')->update(['escalated_to_team' => $netTeam->name]);
        }

        $swTeam = Team::where('code', 'SW')->first();
        if ($swTeam && $swTeam->name !== 'Software') {
            HelpdeskCase::where('escalated_to_team', 'Software')->update(['escalated_to_team' => $swTeam->name]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
