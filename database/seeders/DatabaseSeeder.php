<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Org;
use App\Models\Reading;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create specific users for roles
        $admin = User::firstOrCreate(
            ['email' => 'admin@potts.app'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $client = User::firstOrCreate(
            ['email' => 'client@potts.app'],
            [
                'name' => 'Client User',
                'password' => bcrypt('password'),
                'role' => 'client',
            ]
        );

        $tech = User::firstOrCreate(
            ['email' => 'tech@potts.app'],
            [
                'name' => 'Tech User',
                'password' => bcrypt('password'),
                'role' => 'tech',
            ]
        );

        // 2. Orgs and other standard data
        $org = Org::query()->firstOrCreate(['name' => 'Demo Org']);
        // Assign client to org
        $client->update(['org_id' => $org->id]);

        $this->call([
            MissionSeeder::class,
            DemoDeviceSeeder::class,
        ]);

        // 3. Create extra devices for the Client Dashboard (Demo Org)
        $devices = Device::factory()
            ->count(5)
            ->for($org)
            ->sequence(fn ($sequence) => [
                'device_id' => 'DEM-00'.($sequence->index + 2), // avoiding conflict with pots-001 if needed, though pots-001 is different format
                'name' => 'Pot-'.($sequence->index + 1),
                'location' => [
                    'site' => 'Siège',
                    'floor' => 1,
                    'zone' => 'Bureau '.($sequence->index + 101),
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
