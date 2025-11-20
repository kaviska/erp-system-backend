<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VariationStock>
 */
class VariationStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 5, 300);

        return [
            'product_id' => Product::factory(),
            'sku' => 'VAR-' . strtoupper(Str::random(3)) . '-' . $this->faker->unique()->numerify('#####'),
            'image_path' => 'products/variations/' . $this->faker->word() . '-' . $this->faker->word() . '.jpg',
            'price' => $price,
            'quantity' => $this->faker->numberBetween(0, 150),
            'reserved_quantity' => $this->faker->numberBetween(0, 10),
            'low_stock_threshold' => $this->faker->numberBetween(1, 15),
            'status' => $this->faker->randomElement(['available', 'reserved', 'sold_out']),
            'option_values' => [
                'Color' => $this->faker->safeColorName(),
                'Size' => $this->faker->randomElement(['XS', 'S', 'M', 'L', 'XL']),
            ],
            'metadata' => [
                'notes' => $this->faker->sentence(),
            ],
        ];
    }
}
