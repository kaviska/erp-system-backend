<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'image_path' => 'images/categories/' . Str::uuid() . '.jpg',
            'description' => $this->faker->optional()->sentence(),
            'is_active' => $this->faker->boolean(90),
            'display_order' => $this->faker->numberBetween(1, 50),
        ];
    }
}
