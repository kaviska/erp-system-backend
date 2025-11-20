<?php

namespace Database\Factories;

use App\Models\Variation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VariationOption>
 */
class VariationOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'variation_id' => Variation::factory(),
            'name' => ucfirst($name),
            'value' => $this->faker->hexColor(),
            'additional_price' => $this->faker->randomFloat(2, 0, 20),
            'is_default' => false,
            'display_order' => $this->faker->numberBetween(1, 10),
            'metadata' => [
                'label' => ucfirst($name),
            ],
        ];
    }
}
