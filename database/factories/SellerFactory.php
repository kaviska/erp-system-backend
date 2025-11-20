<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Seller>
 */
class SellerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = $this->faker->unique()->company();

        return [
            'name' => $this->faker->name(),
            'slug' => Str::slug($company) . '-' . Str::random(4),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'shop_name' => $company,
            'shop_slug' => Str::slug($company) . '-' . Str::random(4),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'metadata' => [
                'tax_id' => $this->faker->ssn(),
                'contact_person' => $this->faker->name(),
            ],
            'status' => $this->faker->randomElement(['active', 'pending', 'suspended']),
            'rating' => $this->faker->randomFloat(2, 3.5, 5),
        ];
    }
}
