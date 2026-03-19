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
use Illuminate\Support\Facades\Hash;

class CleanDataMissionSeeder extends Seeder
{
    private ?Carbon $date;

    private ?User $tech;

    /**
     * Run the database seeds.
     *
     * @param  string|null  $date  Date for the missions (Y-m-d)
     * @param  string  $techEmail  Email of the tech user
     */
    public function run(?string $date = null, string $techEmail = 'tech@potts.app')
    {
        $this->date = $date ? Carbon::parse($date) : Carbon::today();
        $this->command->info("Seeding missions for date: {$this->date->toDateString()}");

        $this->seedTechUser($techEmail);
        $this->seedNantesMetropole();
        $this->seedIntersport();
        $this->seedHyperCom();
        $this->seedKoalaTech();
    }

    private function seedTechUser(string $email): void
    {
        $this->tech = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Tech Potts',
                'password' => Hash::make('password'),
                'role' => 'tech',
            ]
        );

        if ($this->tech->role !== 'tech') {
            $this->tech->update(['role' => 'tech']);
        }
    }

    private function seedNantesMetropole(): void
    {
        $org = $this->createOrg('nantes-metropole', 'Nantes Métropole', '2 Cours du Champ de Mars, 44000 Nantes');

        $devices = [
            ['id' => 'NM-001', 'name' => 'Monstera Hall', 'loc' => ['site' => 'Siège', 'floor' => 'Rez-de-chaussée', 'zone' => 'Accueil']],
            ['id' => 'NM-002', 'name' => 'Ficus Bureau Chef', 'loc' => ['site' => 'Siège', 'floor' => 'Étage 2', 'zone' => 'Bureau Direction']],
            ['id' => 'NM-003', 'name' => 'Yucca Salle Pause', 'loc' => ['site' => 'Siège', 'floor' => 'Étage 1', 'zone' => 'Cafétéria']],
            ['id' => 'NM-004', 'name' => 'Palmier Couloir', 'loc' => ['site' => 'Siège', 'floor' => 'Rez-de-chaussée', 'zone' => 'Couloir Est']],
            ['id' => 'NM-005', 'name' => 'Bambou Entrée', 'loc' => ['site' => 'Siège', 'floor' => 'Rez-de-chaussée', 'zone' => 'Sas Entrée']],
        ];

        foreach ($devices as $data) {
            $this->createDevice($org->id, $data['id'], $data['name'], $data['loc']);
        }

        $mission = $this->createMission($org, 'maintenance', 'Entretien mensuel - Siège', 9, 0);

        $missionDevices = Device::whereIn('device_id', array_column($devices, 'id'))->get();
        foreach ($missionDevices as $device) {
            $this->createItem($mission->id, $device->id, 'check');
        }

        $this->createNote($mission->id, 'Entretien standard. Vérifier particulièrement le Ficus du boss.');
    }

    private function seedIntersport(): void
    {
        $org = $this->createOrg('intersport-herblain', 'Intersport Saint-Herblain', '2 Rue du Moulin de la Rousselière, 44800 Saint-Herblain');

        $old1 = $this->createDevice($org->id, 'INTER-OLD-001', 'Plante Fatiguée 1', ['site' => 'Magasin', 'floor' => 'Rayon Running', 'zone' => 'Allée centrale']);
        $old2 = $this->createDevice($org->id, 'INTER-OLD-002', 'Plante Fatiguée 2', ['site' => 'Magasin', 'floor' => 'Caisses', 'zone' => 'Caisse 1']);

        $new1 = $this->createDevice($org->id, 'INTER-NEW-001', 'Plante Neuve 1', ['site' => 'Magasin', 'floor' => 'Rayon Running', 'zone' => 'Allée centrale'], 'inactive');
        $new2 = $this->createDevice($org->id, 'INTER-NEW-002', 'Plante Neuve 2', ['site' => 'Magasin', 'floor' => 'Caisses', 'zone' => 'Caisse 1'], 'inactive');

        $mission = $this->createMission($org, 'replacement', 'Remplacement plantes mourantes', 14, 0);

        $this->createItem($mission->id, $old1->id, 'replace', ['replace_with_device_id' => $new1->id, 'reason' => 'Plante morte']);
        $this->createItem($mission->id, $old2->id, 'replace', ['replace_with_device_id' => $new2->id, 'reason' => 'Plante moche']);

        $this->createNote($mission->id, 'Oubliez pas de reprendre les vieux pots.');
    }

    private function seedHyperCom(): void
    {
        $org = $this->createOrg('hypercom-nantes', 'HyperCom Nantes', '32 Boulevard des Lavandes, 44000 Nantes');
        $device = $this->createDevice($org->id, 'HYPER-001', 'Dracaena Accueil', ['site' => 'Agence', 'floor' => 'RDC', 'zone' => 'Accueil']);

        $mission = $this->createMission($org, 'replacement', 'Remplacement express', 16, 30);
        $this->createItem($mission->id, $device->id, 'replace', ['note' => 'Client pressé']);
    }

    private function seedKoalaTech(): void
    {
        $org = $this->createOrg('koala-tech', 'Koala Tech', '17 Rue du Grand Sapin, 44800 Saint-Herblain');
        $device = $this->createDevice($org->id, 'KOALA-NEW-001', 'Pothos Open Space', ['site' => 'Bâtiment B', 'floor' => 'Étage 1', 'zone' => 'Open Space'], 'inactive');

        $mission = $this->createMission($org, 'installation', 'Installation nouveaux locaux', 11, 0);
        $this->createItem($mission->id, $device->id, 'install', ['location_hint' => 'Près de la machine à café']);
        $this->createNote($mission->id, 'Appeler le client quand on arrive au portail.');
    }

    // --- Helpers ---

    private function createOrg(string $slug, string $name, string $address): Org
    {
        return Org::updateOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'address' => $address, 'status' => 'active']
        );
    }

    private function createDevice(int $orgId, string $deviceId, string $name, array $location, string $status = 'active')
    {
        return Device::updateOrCreate(
            ['device_id' => $deviceId],
            ['org_id' => $orgId, 'name' => $name, 'location' => $location, 'status' => $status]
        );
    }

    private function createMission(Org $org, string $type, string $title, int $hour, int $minute): Mission
    {
        return Mission::updateOrCreate(
            [
                'org_id' => $org->id,
                'type' => $type,
                'scheduled_for' => $this->date->copy()->setTime($hour, $minute, 0),
            ],
            [
                'title' => $title,
                'assigned_to_user_id' => $this->tech->id,
                'address' => $org->address,
                'status' => 'planned',
            ]
        );
    }

    private function createItem(int $missionId, int $deviceId, string $action, ?array $meta = null)
    {
        return MissionItem::updateOrCreate(
            ['mission_id' => $missionId, 'device_id' => $deviceId, 'action' => $action],
            ['status' => 'todo', 'meta' => $meta]
        );
    }

    private function createNote(int $missionId, string $message)
    {
        return MissionNote::updateOrCreate(
            ['mission_id' => $missionId, 'user_id' => $this->tech->id],
            ['message' => $message]
        );
    }
}
