<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'General User',
            'email' => 'user@example.com',
            'role' => 'user',
            'department' => 'HR',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Helpdesk Agent',
            'email' => 'helpdesk@example.com',
            'role' => 'helpdesk',
            'department' => 'IT',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Hardware Specialist',
            'email' => 'hardware@example.com',
            'role' => 'team_hardware',
            'department' => 'IT',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'IT Manager',
            'email' => 'manager@example.com',
            'role' => 'manager',
            'department' => 'IT',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'role' => 'administrator',
            'department' => 'IT',
            'password' => bcrypt('password'),
        ]);

        $this->call(HelpdeskCaseSeeder::class);
    }
}
