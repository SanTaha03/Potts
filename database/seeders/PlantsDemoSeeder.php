<?php

namespace Database\Seeders;

use App\Exceptions\MissingPlantTypesException;
use App\Models\Device;
use App\Models\Org;
use App\Models\Plant;
use App\Models\PlantType;
use Illuminate\Database\Seeder;

class PlantsDemoSeeder extends Seeder
{
    private const BUILDING_A = 'Batiment A';

    private const BUILDING_B = 'Batiment B';

    private const FLOOR_0 = 'Etage 0';

    private const FLOOR_1 = 'Etage 1';

    private const FLOOR_2 = 'Etage 2';

    private const FLOOR_3 = 'Etage 3';

    public function run(): void
    {
        $org = Org::query()->updateOrCreate(
            ['name' => 'SHBF Compagnie'],
            [
                'slug' => 'shbf-compagnie',
                'status' => 'active',
            ]
        );

        $typeSlugs = [
            'monstera-deliciosa',
            'strelitzia-reginae',
            'pachira-aquatica',
            'ficus-lyrata',
            'ocimum-basilicum',
        ];

        $typesBySlug = PlantType::query()
            ->whereIn('slug', $typeSlugs)
            ->get()
            ->keyBy('slug');

        if ($typesBySlug->count() !== count($typeSlugs)) {
            throw MissingPlantTypesException::forSeed();
        }

        $plans = $this->plannedPlants();

        foreach ($plans as $index => $plan) {
            $location = [
                'building' => $plan['building'],
                'floor' => $plan['floor'],
                'zone' => $plan['zone'],
            ];

            $plant = Plant::query()->updateOrCreate(
                [
                    'org_id' => $org->id,
                    'name' => $plan['name'],
                ],
                [
                    'plant_type_id' => $typesBySlug[$plan['plant_type_slug']]->id,
                    'size' => $plan['size'],
                    'location' => $location,
                    'installed_at' => $plan['installed_at'],
                    'status' => 'active',
                ]
            );

            $deviceId = sprintf('POTTS-SHBF-%03d', $index + 1);
            $token = substr(hash('sha256', $deviceId . '-seed'), 0, 64);

            Device::query()->updateOrCreate(
                ['device_id' => $deviceId],
                [
                    'org_id' => $org->id,
                    'plant_id' => $plant->id,
                    'token' => $token,
                    'name' => $plan['name'],
                    'status' => 'active',
                    'location' => $location,
                    'meta' => [
                        'plant_type_slug' => $plan['plant_type_slug'],
                        'seed_source' => 'PlantsDemoSeeder',
                    ],
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function plannedPlants(): array
    {
        return [
            // 7 Monstera: 4M, 2L, 1S
            ['plant_type_slug' => 'monstera-deliciosa', 'name' => 'Monstera Accueil', 'size' => 'M', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_0, 'zone' => 'Reception', 'installed_at' => '2026-01-05'],
            ['plant_type_slug' => 'monstera-deliciosa', 'name' => 'Monstera Open Space A1', 'size' => 'M', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_1, 'zone' => 'Open Space A', 'installed_at' => '2026-01-08'],
            ['plant_type_slug' => 'monstera-deliciosa', 'name' => 'Monstera Open Space A2', 'size' => 'L', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_1, 'zone' => 'Open Space A', 'installed_at' => '2026-01-10'],
            ['plant_type_slug' => 'monstera-deliciosa', 'name' => 'Monstera Salle Reunion 1', 'size' => 'M', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_2, 'zone' => 'Salle de reunion 1', 'installed_at' => '2026-01-12'],
            ['plant_type_slug' => 'monstera-deliciosa', 'name' => 'Monstera Couloir Nord', 'size' => 'S', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_2, 'zone' => 'Couloir Nord', 'installed_at' => '2026-01-14'],
            ['plant_type_slug' => 'monstera-deliciosa', 'name' => 'Monstera Espace Detente', 'size' => 'M', 'building' => self::BUILDING_B, 'floor' => self::FLOOR_1, 'zone' => 'Espace detente', 'installed_at' => '2026-01-16'],
            ['plant_type_slug' => 'monstera-deliciosa', 'name' => 'Monstera Bureau Direction', 'size' => 'L', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_3, 'zone' => 'Bureau Direction', 'installed_at' => '2026-01-18'],

            // 5 Strelitzia: 2M, 3L
            ['plant_type_slug' => 'strelitzia-reginae', 'name' => 'Strelitzia Reception Est', 'size' => 'L', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_0, 'zone' => 'Accueil Est', 'installed_at' => '2026-01-20'],
            ['plant_type_slug' => 'strelitzia-reginae', 'name' => 'Strelitzia Open Space B1', 'size' => 'M', 'building' => self::BUILDING_B, 'floor' => self::FLOOR_1, 'zone' => 'Open Space B', 'installed_at' => '2026-01-22'],
            ['plant_type_slug' => 'strelitzia-reginae', 'name' => 'Strelitzia Salle Reunion 2', 'size' => 'L', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_2, 'zone' => 'Salle de reunion 2', 'installed_at' => '2026-01-24'],
            ['plant_type_slug' => 'strelitzia-reginae', 'name' => 'Strelitzia Cafeteria Sud', 'size' => 'M', 'building' => self::BUILDING_B, 'floor' => self::FLOOR_0, 'zone' => 'Cafeteria', 'installed_at' => '2026-01-26'],
            ['plant_type_slug' => 'strelitzia-reginae', 'name' => 'Strelitzia Bureau RH', 'size' => 'L', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_2, 'zone' => 'Bureau RH', 'installed_at' => '2026-01-28'],

            // 5 Pachira: 3M, 2L
            ['plant_type_slug' => 'pachira-aquatica', 'name' => 'Pachira Couloir Ouest', 'size' => 'M', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_1, 'zone' => 'Couloir Ouest', 'installed_at' => '2026-02-01'],
            ['plant_type_slug' => 'pachira-aquatica', 'name' => 'Pachira Bureau Finance', 'size' => 'M', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_3, 'zone' => 'Bureau Finance', 'installed_at' => '2026-02-03'],
            ['plant_type_slug' => 'pachira-aquatica', 'name' => 'Pachira Meeting Corner', 'size' => 'L', 'building' => self::BUILDING_B, 'floor' => self::FLOOR_1, 'zone' => 'Meeting Corner', 'installed_at' => '2026-02-05'],
            ['plant_type_slug' => 'pachira-aquatica', 'name' => 'Pachira Direction Adjointe', 'size' => 'M', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_3, 'zone' => 'Direction Adjointe', 'installed_at' => '2026-02-07'],
            ['plant_type_slug' => 'pachira-aquatica', 'name' => 'Pachira Espace Client', 'size' => 'L', 'building' => self::BUILDING_B, 'floor' => self::FLOOR_0, 'zone' => 'Espace Client', 'installed_at' => '2026-02-09'],

            // 4 Ficus: 3M, 1L
            ['plant_type_slug' => 'ficus-lyrata', 'name' => 'Ficus Hall Principal', 'size' => 'L', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_0, 'zone' => 'Hall Principal', 'installed_at' => '2026-02-11'],
            ['plant_type_slug' => 'ficus-lyrata', 'name' => 'Ficus Open Space Direction', 'size' => 'M', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_3, 'zone' => 'Open Space Direction', 'installed_at' => '2026-02-13'],
            ['plant_type_slug' => 'ficus-lyrata', 'name' => 'Ficus Salle Conseil', 'size' => 'M', 'building' => self::BUILDING_A, 'floor' => self::FLOOR_3, 'zone' => 'Salle Conseil', 'installed_at' => '2026-02-15'],
            ['plant_type_slug' => 'ficus-lyrata', 'name' => 'Ficus Zone Silence', 'size' => 'M', 'building' => self::BUILDING_B, 'floor' => self::FLOOR_2, 'zone' => 'Zone Silence', 'installed_at' => '2026-02-17'],

            // 4 Basilic: 4S
            ['plant_type_slug' => 'ocimum-basilicum', 'name' => 'Basilic Cuisine Nord', 'size' => 'S', 'building' => self::BUILDING_B, 'floor' => self::FLOOR_0, 'zone' => 'Cuisine Nord', 'installed_at' => '2026-02-19'],
            ['plant_type_slug' => 'ocimum-basilicum', 'name' => 'Basilic Cafeteria Herbes', 'size' => 'S', 'building' => self::BUILDING_B, 'floor' => self::FLOOR_0, 'zone' => 'Cafeteria Herbes', 'installed_at' => '2026-02-21'],
            ['plant_type_slug' => 'ocimum-basilicum', 'name' => 'Basilic Terrasse Interieure', 'size' => 'S', 'building' => self::BUILDING_B, 'floor' => self::FLOOR_1, 'zone' => 'Terrasse Interieure', 'installed_at' => '2026-02-23'],
            ['plant_type_slug' => 'ocimum-basilicum', 'name' => 'Basilic Coin Degustation', 'size' => 'S', 'building' => self::BUILDING_B, 'floor' => self::FLOOR_0, 'zone' => 'Coin Degustation', 'installed_at' => '2026-02-25'],
        ];
    }
}
