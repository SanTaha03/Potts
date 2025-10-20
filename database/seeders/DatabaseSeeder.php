<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Org;
use App\Models\Device;
use App\Models\Reading;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $org = Org::query()->firstOrCreate(['name'=>'Demo Org']);
        $admin = User::query()->firstOrCreate(
            ['email'=>'admin@demo.test'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'org_id' => $org->id,
                'role' => 'admin'
            ]
        );

        // 5 devices + quelques readings
        for ($i=1; $i<=5; $i++) {
            $d = Device::create([
                'org_id' => $org->id,
                'serial' => 'DEM'.Str::padLeft((string)$i, 5, '0'),
                'alias'  => "Pot-$i",
                'status' => 'active',
                'location' => ['site'=>'HQ','floor'=>1,'zone'=>"Z$i"],
            ]);

            // 10 mesures humidité
            for ($j=0; $j<10; $j++) {
                Reading::create([
                'device_id' => $d->id,
                'sensor_type' => 'moisture',
                'value' => rand(300,700)/10,
                'measured_at' => now()->subHours(10-$j),
                ]);
            }
        }
    }
}
