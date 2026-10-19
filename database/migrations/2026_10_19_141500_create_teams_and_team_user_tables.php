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
        // 1. Create teams table
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Create team_user pivot table
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_in_team')->default('member'); // 'lead', 'member'
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });

        // 3. Seed default teams & connect existing users for seamless backward compatibility
        $defaultTeams = [
            [
                'name' => 'Software',
                'code' => 'SW',
                'description' => 'ทีมพัฒนาและดูแลระบบโปรแกรม ซอฟต์แวร์ และแอปพลิเคชัน',
                'manager_role' => 'manager_software',
                'member_role' => 'team_software',
            ],
            [
                'name' => 'Hardware',
                'code' => 'HW',
                'description' => 'ทีมซ่อมบำรุงและดูแลอุปกรณ์คอมพิวเตอร์และฮาร์ดแวร์สำนักงาน',
                'manager_role' => 'manager_network_hardware',
                'member_role' => 'team_hardware',
            ],
            [
                'name' => 'Network',
                'code' => 'NET',
                'description' => 'ทีมดูแลระบบเครือข่าย อินเทอร์เน็ต ไฟร์วอลล์ และโครงสร้างพื้นฐาน',
                'manager_role' => 'manager_network_hardware',
                'member_role' => 'team_network',
            ],
        ];

        foreach ($defaultTeams as $item) {
            // Find manager if any exists
            $manager = DB::table('users')
                ->where('role', $item['manager_role'])
                ->orWhere('role', 'manager')
                ->first();

            $teamId = DB::table('teams')->insertGetId([
                'name' => $item['name'],
                'code' => $item['code'],
                'description' => $item['description'],
                'manager_id' => $manager?->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Attach existing members with matching legacy role
            $members = DB::table('users')->where('role', $item['member_role'])->get();
            foreach ($members as $member) {
                DB::table('team_user')->insert([
                    'team_id' => $teamId,
                    'user_id' => $member->id,
                    'role_in_team' => 'member',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');
    }
};
