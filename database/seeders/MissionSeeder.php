<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Mission;
use App\Models\MissionItem;
use App\Models\MissionNote;
use App\Models\Org;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure we have a technician user
        $tech = User::firstOrCreate(
            ['email' => 'tech@potts.app'],
            [
                'name' => 'Jean Michel',
                'password' => bcrypt('password'),
            ]
        );

        // 2. Ensure Clients
        $shbf = Org::firstOrCreate(['name' => 'SHBF Compagnie'], ['slug' => 'shbf', 'address' => '12 rue de la paix, St-Herblain']);
        $intersport = Org::firstOrCreate(['name' => 'Intersport'], ['slug' => 'intersport', 'address' => '02 avenue du marché, St-Herblain']);
        $koala = Org::firstOrCreate(['name' => 'Koala Compagnie'], ['slug' => 'koala', 'address' => '32 bd des lavandes, Nantes']);
        $hypercom = Org::firstOrCreate(['name' => 'HyperCom'], ['slug' => 'hypercom', 'address' => '17 rue du grand sapin, St-Herblain']);

        // 3. Create Missions for Today
        $today = Carbon::today()->addHours(9); // Start at 9am

        // Mission 1: Entretien régulier (SHBF)
        $m1 = Mission::create([
            'org_id' => $shbf->id,
            'assigned_to_user_id' => $tech->id,
            'type' => 'maintenance',
            'title' => 'Entretien régulier',
            'address' => $shbf->address,
            'status' => 'planned',
            'scheduled_for' => $today->copy(),
        ]);

        // Add Specific Item (Monsterrat #12345)
        $monstera = Device::factory()->create([
            'org_id' => $shbf->id,
            'device_id' => '124456',
            'name' => 'Monsterrat',
            'location' => [
                'site' => 'Bat. A',
                'floor' => 'Étage 0',
                'zone' => 'Bureau L1',
            ],
        ]);

        MissionItem::create([
            'mission_id' => $m1->id,
            'device_id' => $monstera->id,
            'action' => 'check',
            'status' => 'todo',
        ]);

        // Add 4 other random items for context
        for ($i = 2; $i <= 5; $i++) {
            $device = Device::factory()->create([
                'org_id' => $shbf->id,
                'name' => 'Ficus '.$i,
                'location' => ['site' => 'Bat. A', 'floor' => 'Étage 0', 'zone' => 'Bureau L'.$i],
            ]);

            MissionItem::create([
                'mission_id' => $m1->id,
                'device_id' => $device->id,
                'action' => 'check',
                'status' => 'todo',
            ]);
        }

        // Add Notes
        MissionNote::create([
            'mission_id' => $m1->id,
            'user_id' => $tech->id,
            'message' => 'Attention l’entrée est en travaux, passer par l’est.',
        ]);

        // Mission 2: Remplacement de plantes (Intersport)
        $m2 = Mission::create([
            'org_id' => $intersport->id,
            'assigned_to_user_id' => $tech->id,
            'type' => 'replacement',
            'title' => 'Remplacement de plantes',
            'address' => $intersport->address,
            'status' => 'planned',
            'scheduled_for' => $today->copy()->addHours(2),
        ]);

        // Items for replacement
        // Create 2 "dead" plants and 2 "new" plants
        for ($i = 0; $i < 2; $i++) {
            $old = Device::factory()->create(['org_id' => $intersport->id]);
            $new = Device::factory()->create(['org_id' => $intersport->id]);

            MissionItem::create([
                'mission_id' => $m2->id,
                'device_id' => $new->id, // The one to install
                'action' => 'replace',
                'status' => 'todo',
                'meta' => [
                    'old_device_id' => $old->id,
                    'old_device_name' => 'Monstera ',
                    'old_device_code' => $old->device_id,
                    'new_device_name' => 'Monstera #'.$new->device_id,
                    'location' => 'Bat. A, Etage 2',
                ],
            ]);
        }

        // Mission 3: Remplacement de plantes (HyperCom) - Juste pour matcher la liste
        Mission::create([
            'org_id' => $hypercom->id,
            'assigned_to_user_id' => $tech->id,
            'type' => 'replacement',
            'title' => 'Remplacement de plantes',
            'address' => $hypercom->address,
            'status' => 'planned',
            'scheduled_for' => $today->copy()->addHours(4),
        ]);

        // Mission 4: Installation (Koala)
        Mission::create([
            'org_id' => $koala->id,
            'assigned_to_user_id' => $tech->id,
            'type' => 'installation',
            'title' => 'Installation de plantes',
            'address' => $koala->address,
            'status' => 'planned',
            'scheduled_for' => $today->copy()->addHours(6),
        ]);
    }
}
