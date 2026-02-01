<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Org;
use App\Models\Reading;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        /*User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);*/

        $org = Org::query()->firstOrCreate(['name' => 'Demo Org']);
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@demo.test'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'org_id' => $org->id,
                'role' => 'admin'
            ]
        );

        $devices = Device::factory()
            ->count(5)
            ->for($org)
            ->sequence(fn ($sequence) => [
                'serial' => 'DEM' . Str::padLeft((string) ($sequence->index + 1), 5, '0'),
                'alias' => 'Pot-' . ($sequence->index + 1),
                'location' => [
                    'site' => 'HQ', // "Headquarters" = "Siège"
                    'floor' => 1,
                    'zone' => 'Z' . ($sequence->index + 1),
                ],
            ])
            ->create();

        $devices->each(function (Device $device) {
            for ($j = 0; $j < 10; $j++) {
                Reading::create([
                    'device_id' => $device->id,
                    'sensor_type' => 'moisture',
                    'value' => rand(300, 700) / 10,
                    'measured_at' => now()->subHours(10 - $j),
                ]);
            }
        });
    }
}
