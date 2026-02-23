<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Org;
use Illuminate\Database\Seeder;

class DemoDeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $org = Org::firstOrCreate(
            ['name' => 'Demo Org'],
            ['status' => 'active']
        );
        Device::updateOrCreate(
            ['device_id' => 'pots-001'],
            [
                'name' => 'Pot Démo',
                'token' => 'pottS_demo_2026_X9a7K2mPq1',
                'status' => 'unknown',
                'meta' => [
                    'notes' => 'Device de demo pour ingestion hardware',
                ],
                'org_id' => $org->id,
            ]
        );
    }
}
