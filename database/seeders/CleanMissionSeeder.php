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

class CleanMissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param  string|null  $date  Date for the missions (Y-m-d)
     * @param  string  $techEmail  Email of the tech user
     */
    public function run(?string $date = null, string $techEmail = 'tech@potts.app')
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();
        $this->command->info("Seeding missions for date: {$date->toDateString()}");

        // 1. Tech User (Producteur)
        $tech = User::firstOrCreate(
            ['email' => $techEmail],
            [
                'name' => 'Tech Potts',
                'password' => Hash::make('password'), // Mettre un password par defaut
                'role' => 'tech', // Assure-toi que ce champ existe dans ta table users
            ]
        );

        // Update role if created without it or changed
        if ($tech->role !== 'tech') {
            $tech->update(['role' => 'tech']);
        }

        // 2. Org: Nantes Métropole (Client Principal)
        $orgNantes = Org::updateOrCreate(
            ['slug' => 'nantes-metropole'],
            [
                'name' => 'Nantes Métropole',
                'address' => '2 Cours du Champ de Mars, 44000 Nantes',
                'status' => 'active',
            ]
        );

        // 3. Org: Intersport (Client Secondaire)
        $orgIntersport = Org::updateOrCreate(
            ['slug' => 'intersport-herblain'],
            [
                'name' => 'Intersport Saint-Herblain',
                'address' => '2 Rue du Moulin de la Rousselière, 44800 Saint-Herblain',
                'status' => 'active',
            ]
        );

        // 4. Devices (Nantes Métropole)
        $devicesNantes = [
            [
                'device_id' => 'POTTS-NM-001',
                'name' => 'Monstera Hall',
                'location' => ['site' => 'Siège', 'floor' => 'Rez-de-chaussée', 'zone' => 'Accueil'],
                'status' => 'active',
            ],
            [
                'device_id' => 'POTTS-NM-002',
                'name' => 'Ficus Bureau Chef',
                'location' => ['site' => 'Siège', 'floor' => 'Étage 2', 'zone' => 'Bureau Direction'],
                'status' => 'active',
            ],
            [
                'device_id' => 'POTTS-NM-003',
                'name' => 'Yucca Salle Pause',
                'location' => ['site' => 'Siège', 'floor' => 'Étage 1', 'zone' => 'Cafétéria'],
                'status' => 'active',
            ],
            [
                'device_id' => 'POTTS-NM-004',
                'name' => 'Palmier Couloir',
                'location' => ['site' => 'Siège', 'floor' => 'Rez-de-chaussée', 'zone' => 'Couloir Est'],
                'status' => 'active',
            ],
            [
                'device_id' => 'POTTS-NM-005',
                'name' => 'Bambou Entrée',
                'location' => ['site' => 'Siège', 'floor' => 'Rez-de-chaussée', 'zone' => 'Sas Entrée'],
                'status' => 'active',
            ],
        ];

        foreach ($devicesNantes as $devData) {
            Device::updateOrCreate(
                ['device_id' => $devData['device_id']],
                [
                    'org_id' => $orgNantes->id,
                    'name' => $devData['name'],
                    'location' => $devData['location'], // Laravel cast array to json if model configured
                    'status' => $devData['status'],
                    // 'token' => Str::random(32), // Optional if not critical for seeding
                ]
            );
        }

        // 5. Devices (Intersport) - Remplacement context
        // Old devices (to be replaced)
        $deviceOld1 = Device::updateOrCreate(
            ['device_id' => 'POTTS-INTER-OLD-001'],
            [
                'org_id' => $orgIntersport->id,
                'name' => 'Plante Fatiguée 1',
                'location' => ['site' => 'Magasin', 'floor' => 'Rayon Running', 'zone' => 'Allée centrale'],
                'status' => 'active',
            ]
        );

        $deviceOld2 = Device::updateOrCreate(
            ['device_id' => 'POTTS-INTER-OLD-002'],
            [
                'org_id' => $orgIntersport->id,
                'name' => 'Plante Fatiguée 2',
                'location' => ['site' => 'Magasin', 'floor' => 'Caisses', 'zone' => 'Caisse 1'],
                'status' => 'active',
            ]
        );

        // New devices (replacements)
        $deviceNew1 = Device::updateOrCreate(
            ['device_id' => 'POTTS-INTER-NEW-001'],
            [
                'org_id' => $orgIntersport->id,
                'name' => 'Plante Neuve 1',
                'location' => ['site' => 'Magasin', 'floor' => 'Rayon Running', 'zone' => 'Allée centrale'],
                'status' => 'inactive', // Not yet installed
            ]
        );

        $deviceNew2 = Device::updateOrCreate(
            ['device_id' => 'POTTS-INTER-NEW-002'],
            [
                'org_id' => $orgIntersport->id,
                'name' => 'Plante Neuve 2',
                'location' => ['site' => 'Magasin', 'floor' => 'Caisses', 'zone' => 'Caisse 1'],
                'status' => 'inactive',
            ]
        );

        // --- Missions ---

        // Mission 1: Entretien régulier (Nantes Métropole) - 5 plantes
        $mission1Title = 'Entretien mensuel - Siège';
        $mission1 = Mission::updateOrCreate(
            [
                'org_id' => $orgNantes->id,
                'type' => 'maintenance',
                'scheduled_for' => $date->copy()->setTime(9, 0, 0),
            ],
            [
                'title' => $mission1Title,
                'assigned_to_user_id' => $tech->id,
                'address' => $orgNantes->address,
                'status' => 'planned',
            ]
        );

        // Items for Mission 1
        $devicesNantesModels = Device::whereIn('device_id', array_column($devicesNantes, 'device_id'))->get();
        foreach ($devicesNantesModels as $device) {
            MissionItem::updateOrCreate(
                [
                    'mission_id' => $mission1->id,
                    'device_id' => $device->id,
                    'action' => 'check',
                ],
                [
                    'status' => 'todo',
                    'meta' => null,
                ]
            );
        }

        // Note for Mission 1
        MissionNote::updateOrCreate(
            [
                'mission_id' => $mission1->id,
                'user_id' => $tech->id,
                'message' => 'Entretien standard. Vérifier particulièrement le Ficus du boss.',
            ]
        );

        // Mission 2: Remplacement (Intersport) - 2 devices
        $mission2Title = 'Remplacement plantes mourantes';
        $mission2 = Mission::updateOrCreate(
            [
                'org_id' => $orgIntersport->id,
                'type' => 'replacement',
                'scheduled_for' => $date->copy()->setTime(14, 0, 0),
            ],
            [
                'title' => $mission2Title,
                'assigned_to_user_id' => $tech->id,
                'address' => $orgIntersport->address,
                'status' => 'planned',
            ]
        );

        // Items for Mission 2
        // Item 1: Replace Old 1 with New 1
        MissionItem::updateOrCreate(
            [
                'mission_id' => $mission2->id,
                'device_id' => $deviceOld1->id,
                'action' => 'replace',
            ],
            [
                'status' => 'todo',
                'meta' => [
                    'replace_with_device_id' => $deviceNew1->id,
                    'reason' => 'Plante morte',
                ],
            ]
        );

        // Item 2: Replace Old 2 with New 2
        MissionItem::updateOrCreate(
            [
                'mission_id' => $mission2->id,
                'device_id' => $deviceOld2->id,
                'action' => 'replace',
            ],
            [
                'status' => 'todo',
                'meta' => [
                    'replace_with_device_id' => $deviceNew2->id,
                    'reason' => 'Plante moche',
                ],
            ]
        );

        MissionNote::updateOrCreate(
            [
                'mission_id' => $mission2->id,
                'user_id' => $tech->id, // Note from tech is fine, or create a client user
                'message' => 'Oubliez pas de reprendre les vieux pots.',
            ]
        );

        // Mission 3: Remplacement (HyperCom - new org on the fly or reuse)
        // Let's create another Org "HyperCom" for Mission 3 as requested
        $orgHyperCom = Org::updateOrCreate(
            ['slug' => 'hypercom-nantes'],
            [
                'name' => 'HyperCom Nantes',
                'address' => '32 Boulevard des Lavandes, 44000 Nantes', // Clean address
                'status' => 'active',
            ]
        );

        // Devices for HyperCom
        $deviceHyper1 = Device::updateOrCreate(
            ['device_id' => 'POTTS-HYPER-001'],
            [
                'org_id' => $orgHyperCom->id,
                'name' => 'Dracaena Accueil',
                'location' => ['site' => 'Agence', 'floor' => 'RDC', 'zone' => 'Accueil'],
                'status' => 'active',
            ]
        );

        $mission3 = Mission::updateOrCreate(
            [
                'org_id' => $orgHyperCom->id,
                'type' => 'replacement', // As requested "Remplacement (HyperCom)"
                'scheduled_for' => $date->copy()->setTime(16, 30, 0),
            ],
            [
                'title' => 'Remplacement express',
                'assigned_to_user_id' => $tech->id,
                'address' => $orgHyperCom->address,
                'status' => 'planned',
            ]
        );

        MissionItem::updateOrCreate(
            [
                'mission_id' => $mission3->id,
                'device_id' => $deviceHyper1->id,
                'action' => 'replace',
            ],
            [
                'status' => 'todo',
                'meta' => ['note' => 'Client pressé'],
            ]
        );

        // Mission 4: Installation (Koala)
        $orgKoala = Org::updateOrCreate(
            ['slug' => 'koala-tech'],
            [
                'name' => 'Koala Tech',
                'address' => '17 Rue du Grand Sapin, 44800 Saint-Herblain', // Clean address
                'status' => 'active',
            ]
        );

        // No existing devices yet, purely installation
        // Wait, for installation, maybe we create the "future" devices as inactive?
        // Or leave device_id null in items if they are not system registered yet?
        // For Potts logic, usually "installation" implies bringing NEW devices.

        $deviceKoala1 = Device::updateOrCreate(
            ['device_id' => 'POTTS-KOALA-NEW-001'],
            [
                'org_id' => $orgKoala->id,
                'name' => 'Pothos Open Space',
                'location' => ['site' => 'Bâtiment B', 'floor' => 'Étage 1', 'zone' => 'Open Space'],
                'status' => 'inactive',
            ]
        );

        $mission4 = Mission::updateOrCreate(
            [
                'org_id' => $orgKoala->id,
                'type' => 'installation',
                'scheduled_for' => $date->copy()->setTime(11, 0, 0),
            ],
            [
                'title' => 'Installation nouveaux locaux',
                'assigned_to_user_id' => $tech->id,
                'address' => $orgKoala->address, // Clean address
                'status' => 'planned',
            ]
        );

        MissionItem::updateOrCreate(
            [
                'mission_id' => $mission4->id,
                'device_id' => $deviceKoala1->id,
                'action' => 'install',
            ],
            [
                'status' => 'todo',
                'meta' => ['location_hint' => 'Près de la machine à café'],
            ]
        );

        // Add note for completeness
        MissionNote::updateOrCreate(
            [
                'mission_id' => $mission4->id,
                'user_id' => $tech->id,
                'message' => 'Appeler le client quand on arrive au portail.',
            ]
        );
    }
}
