<?php

namespace Database\Seeders;

use App\Models\PlantType;
use Illuminate\Database\Seeder;

class PlantTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'slug' => 'monstera-deliciosa',
                'display_name' => 'Monstera deliciosa',
                'category' => 'tropicale',
                'description' => 'Plante tropicale d interieur a croissance vigoureuse.',
                'soil_opt_min' => 35,
                'soil_opt_max' => 75,
                'temp_opt_min' => 18.0,
                'temp_opt_max' => 28.0,
                'light_opt_min' => 25,
                'light_opt_max' => 85,
                'co2_k_per_m2_year' => 1.70,
                'leaf_area_m2_s' => 0.30,
                'leaf_area_m2_m' => 0.50,
                'leaf_area_m2_l' => 0.80,
            ],
            [
                'slug' => 'strelitzia-reginae',
                'display_name' => 'Strelitzia reginae',
                'category' => 'tropicale',
                'description' => 'Oiseau du paradis, bon comportement en milieu lumineux.',
                'soil_opt_min' => 30,
                'soil_opt_max' => 70,
                'temp_opt_min' => 18.0,
                'temp_opt_max' => 30.0,
                'light_opt_min' => 35,
                'light_opt_max' => 95,
                'co2_k_per_m2_year' => 1.90,
                'leaf_area_m2_s' => 0.35,
                'leaf_area_m2_m' => 0.60,
                'leaf_area_m2_l' => 1.00,
            ],
            [
                'slug' => 'pachira-aquatica',
                'display_name' => 'Pachira aquatica',
                'category' => 'tropicale',
                'description' => 'Plante d interieur decorative, apprecie une humidite stable.',
                'soil_opt_min' => 35,
                'soil_opt_max' => 70,
                'temp_opt_min' => 18.0,
                'temp_opt_max' => 27.0,
                'light_opt_min' => 20,
                'light_opt_max' => 75,
                'co2_k_per_m2_year' => 1.60,
                'leaf_area_m2_s' => 0.28,
                'leaf_area_m2_m' => 0.48,
                'leaf_area_m2_l' => 0.75,
            ],
            [
                'slug' => 'ocimum-basilicum',
                'display_name' => 'Ocimum basilicum',
                'category' => 'aromatique',
                'description' => 'Basilic, plante aromatique demandant chaleur et lumiere.',
                'soil_opt_min' => 40,
                'soil_opt_max' => 80,
                'temp_opt_min' => 20.0,
                'temp_opt_max' => 30.0,
                'light_opt_min' => 45,
                'light_opt_max' => 95,
                'co2_k_per_m2_year' => 1.20,
                'leaf_area_m2_s' => 0.12,
                'leaf_area_m2_m' => 0.20,
                'leaf_area_m2_l' => 0.30,
            ],
            [
                'slug' => 'ficus-lyrata',
                'display_name' => 'Ficus lyrata',
                'category' => 'tropicale',
                'description' => 'Figuier lyre, tres utilise dans les espaces de bureau premium.',
                'soil_opt_min' => 30,
                'soil_opt_max' => 65,
                'temp_opt_min' => 18.0,
                'temp_opt_max' => 27.0,
                'light_opt_min' => 30,
                'light_opt_max' => 85,
                'co2_k_per_m2_year' => 2.00,
                'leaf_area_m2_s' => 0.40,
                'leaf_area_m2_m' => 0.75,
                'leaf_area_m2_l' => 1.20,
            ],
        ];

        foreach ($types as $type) {
            PlantType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
