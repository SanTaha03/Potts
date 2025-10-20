<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'org_id'  => null, // on pourra l’écraser à la création
            'serial'  => 'DEM' . $this->faker->unique()->numerify('#####'),
            'alias'   => $this->faker->randomElement([null, 'Pot '.$this->faker->numberBetween(1,50)]),
            'status'  => 'active',
            'location'=> ['site'=>'HQ','floor'=>1,'zone'=>'Z'.$this->faker->numberBetween(1,9)],
            'meta'    => null,
        ];
    }
}
