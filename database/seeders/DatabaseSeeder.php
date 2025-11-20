<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            SubCategorySeeder::class,
            SellerSeeder::class,
            ProductSeeder::class,
            VariationSeeder::class,
            VariationOptionSeeder::class,
            VariationStockSeeder::class,
        ]);

        User::factory(10)->create();

        User::factory()->create([
            'first_name' => 'Test First',
            'last_name' => 'Test Last',
            'email' => 'test@example.com',
            'password' => Hash::make('Test@123'),
        ]);

        User::factory()->create([
            'first_name' => 'Kaviska Dilshan',
            'last_name' => 'Munasinghe Dewage',
            'email' => 'kaviska525@gmail.com',
            'password' => Hash::make('Malidunew@123'),
        ]);
    }
}
