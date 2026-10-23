<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'          => $this->faker->words(3, true),
            'category'      => $this->faker->randomElement(['Optik', 'Analitik', 'Elektronik', 'Pemisahan']),
            'serial_number' => strtoupper($this->faker->bothify('??-###-####-???')),
            'condition'     => $this->faker->randomElement(['baik', 'rusak_ringan', 'rusak_berat']),
            'quantity'      => $this->faker->numberBetween(1, 20),
        ];
    }
}
