<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Variation>
 */
class VariationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement(['Color', 'Size', 'Material', 'Style']);

        return [
            'product_id' => Product::factory(),
            'name' => $name,
            'type' => 'select',
            'is_required' => $this->faker->boolean(80),
            'display_order' => $this->faker->numberBetween(1, 5),
            'configuration' => [
                'presentation' => $this->faker->randomElement(['pill', 'dropdown', 'swatch']),
            ],
        ];
    }
}
