<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        $price = $this->faker->randomFloat(2, 10, 500);
        $salePrice = $this->faker->boolean(40) ? $price - $this->faker->randomFloat(2, 1, 20) : null;

        return [
            'seller_id' => Seller::factory(),
            'category_id' => Category::factory(),
            'sub_category_id' => null,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'sku' => strtoupper(Str::random(3)) . '-' . $this->faker->unique()->numerify('#####'),
            'barcode' => $this->faker->optional()->ean13(),
            'type' => $this->faker->randomElement(['physical', 'digital']),
            'brand' => $this->faker->company(),
            'short_description' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'lead_time' => $this->faker->numberBetween(0, 5),
            'price' => $price,
            'sale_price' => $salePrice,
            'currency' => 'USD',
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'track_inventory' => $this->faker->boolean(80),
            'is_published' => $this->faker->boolean(70),
            'published_at' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('-1 year', 'now') : null,
            'status' => $this->faker->randomElement(['draft', 'active', 'archived']),
            'metadata' => [
                'tags' => $this->faker->words(3),
                'warranty' => $this->faker->numberBetween(0, 24) . ' months',
            ],
        ];
    }
}
