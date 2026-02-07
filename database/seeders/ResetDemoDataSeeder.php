<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Org;
use App\Models\Reading;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResetDemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Identify Demo Org
        // Slug column might not exist in orgs table based on error, removing slug
        $org = Org::firstOrCreate(
            ['name' => 'Org Démo']
        );

        // 2. Define our Hero Device
        $heroDeviceId = 'pots-001';
        $heroToken = 'pottS_demo_2026_X9a7K2mPq1';

        // 3. Clean up Readings first (FK constraint)
        // Delete all readings where device is NOT our hero
        // But first we need to find the hero ID if it exists
        $existingHero = Device::where('device_id', $heroDeviceId)->first();
        
        if ($existingHero) {
            Reading::where('device_id', '!=', $existingHero->id)->delete();
        } else {
            Reading::truncate(); // No hero, nuke all
        }

        // 4. Clean up Devices
        Device::where('device_id', '!=', $heroDeviceId)->delete();

        // 5. Create or Update Hero Device
        $device = Device::updateOrCreate(
            ['device_id' => $heroDeviceId],
            [
                'org_id' => $org->id,
                'name' => 'Pot Démo',
                'token' => $heroToken,
                'status' => 'active',
                'location' => [
                    'site' => 'Bâtiment A',
                    'floor' => 1,
                    'zone' => 'Accueil'
                ],
                'meta' => [
                    'notes' => 'Appareil de démonstration',
                    'model' => 'ESP32-S3-Mini',
                    'installed_at' => now()->toDateString()
                ],
                // On ne touche pas à last_values / last_seen_at pour garder l'état courant
                // Sauf si on veut tout flusher : 
                // 'last_values' => null, 
                // 'last_seen_at' => null
            ]
        );

        $this->command->info("✅ Demo Environment Reset!");
        $this->command->info("Device: {$device->device_id} / Token: {$device->token}");
    }
}
