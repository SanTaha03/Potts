<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mission;
use App\Models\MissionItem;
use App\Models\MissionNote;
use App\Models\Org;
use App\Models\User;
use App\Models\Device;
use Carbon\Carbon;

class AddDataTech extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Retrieve Tech User
        $tech = User::where('email', 'tech@potts.app')->firstOrFail();

        // 2. Retrieve Orgs (create if missing for robustness)
        $shbf = Org::firstOrCreate(['name' => 'SHBF Compagnie'], ['slug' => 'shbf', 'address' => '12 rue de la paix, St-Herblain']);
        $intersport = Org::firstOrCreate(['name' => 'Intersport'], ['slug' => 'intersport', 'address' => '02 avenue du marché, St-Herblain']);
        $koala = Org::firstOrCreate(['name' => 'Koala Compagnie'], ['slug' => 'koala', 'address' => '32 bd des lavandes, Nantes']);

        $today = Carbon::today();

        // --- MISSION 1: ENTRETIEN (SHBF) ---
        $m1 = Mission::create([
            'org_id' => $shbf->id,
            'assigned_to_user_id' => $tech->id,
            'type' => 'maintenance',
            'title' => 'Entretien du Hall et de l\'Accueil',
            'address' => $shbf->address,
            'status' => 'planned',
            'scheduled_for' => $today->copy()->addHours(9),
        ]);

        // Item 1: Monstera Géant
        $dev1 = Device::factory()->create([
            'org_id' => $shbf->id,
            'name' => 'Monstera Deliciosa',
            'device_id' => 'SHBF-001',
            'location' => ['site' => 'Siège', 'floor' => 'RDC', 'zone' => 'Hall d\'entrée']
        ]);
        MissionItem::create([
            'mission_id' => $m1->id,
            'device_id' => $dev1->id,
            'action' => 'check',
            'status' => 'todo',
        ]);

        // Item 2: Ficus Lyrata
        $dev2 = Device::factory()->create([
            'org_id' => $shbf->id,
            'name' => 'Ficus Lyrata',
            'device_id' => 'SHBF-002',
            'location' => ['site' => 'Siège', 'floor' => 'RDC', 'zone' => 'Accueil']
        ]);
        MissionItem::create([
            'mission_id' => $m1->id,
            'device_id' => $dev2->id,
            'action' => 'check',
            'status' => 'todo',
        ]);

        MissionNote::create([
            'mission_id' => $m1->id,
            'user_id' => $tech->id,
            'message' => 'Le Ficus perd beaucoup de feuilles, vérifier l\'arrosage.',
        ]);


        // --- MISSION 2: REMPLACEMENT (INTERSPORT) ---
        $m2 = Mission::create([
            'org_id' => $intersport->id,
            'assigned_to_user_id' => $tech->id,
            'type' => 'replacement',
            'title' => 'Remplacement plantes mortes',
            'address' => $intersport->address,
            'status' => 'planned',
            'scheduled_for' => $today->copy()->addHours(11),
        ]);

        // Item 1: Yucca -> Palmier
        $old1 = Device::factory()->create(['org_id' => $intersport->id, 'name' => 'Yucca Mort', 'status' => 'inactive']);
        $new1 = Device::factory()->create([
            'org_id' => $intersport->id, 
            'name' => 'Palmier Kentia', 
            'device_id' => 'INT-NEW-01',
            'location' => ['site' => 'Magasin', 'floor' => '1', 'zone' => 'Rayon Running']
        ]);
        
        MissionItem::create([
            'mission_id' => $m2->id,
            'device_id' => $new1->id,
            'action' => 'replace',
            'status' => 'todo',
            'meta' => [
                'old_device_id' => $old1->id,
                'old_device_name' => $old1->name,
                'new_device_name' => $new1->name,
                'new_device_id' => $new1->device_id,
            ]
        ]);

        // Item 2: Dracaena -> Areca
        $old2 = Device::factory()->create(['org_id' => $intersport->id, 'name' => 'Dracaena Sec', 'status' => 'inactive']);
        $new2 = Device::factory()->create([
            'org_id' => $intersport->id, 
            'name' => 'Areca Dypsis', 
            'device_id' => 'INT-NEW-02',
            'location' => ['site' => 'Magasin', 'floor' => '1', 'zone' => 'Caisses']
        ]);

        MissionItem::create([
            'mission_id' => $m2->id,
            'device_id' => $new2->id,
            'action' => 'replace',
            'status' => 'todo',
            'meta' => [
                'old_device_id' => $old2->id,
                'old_device_name' => $old2->name,
                'new_device_name' => $new2->name,
                'new_device_id' => $new2->device_id,
            ]
        ]);


        // --- MISSION 3: INSTALLATION (KOALA) ---
        $m3 = Mission::create([
            'org_id' => $koala->id,
            'assigned_to_user_id' => $tech->id,
            'type' => 'installation',
            'title' => 'Végétalisation Salle de Pause',
            'address' => $koala->address,
            'status' => 'planned',
            'scheduled_for' => $today->copy()->addHours(14),
        ]);

        // Item 1: Cactus Géant
        $cal1 = Device::factory()->create([
            'org_id' => $koala->id,
            'name' => 'Cactus Euphorbia',
            'device_id' => 'KOA-INS-01',
            'location' => ['site' => 'Bureaux', 'floor' => '2', 'zone' => 'Salle de pause']
        ]);
        MissionItem::create([
            'mission_id' => $m3->id,
            'device_id' => $cal1->id,
            'action' => 'install',
            'status' => 'todo',
        ]);

        // Item 2: Pothos Suspendu
        $cal2 = Device::factory()->create([
            'org_id' => $koala->id,
            'name' => 'Pothos Golden',
            'device_id' => 'KOA-INS-02',
            'location' => ['site' => 'Bureaux', 'floor' => '2', 'zone' => 'Salle de pause (étagère)']
        ]);
        MissionItem::create([
            'mission_id' => $m3->id,
            'device_id' => $cal2->id,
            'action' => 'install',
            'status' => 'todo',
        ]);
    }
}
