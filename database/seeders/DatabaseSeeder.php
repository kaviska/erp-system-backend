<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         User::factory(10)->create();

        User::factory()->create([
            'first_name' => 'Test First',
            'last_name' => 'Test Last',
            'email' => 'test@example.com',
            'password' => 'Test@123',
        ],);

         User::factory()->create([
            'first_name' => 'Kaviska Dilshan',
            'last_name' => 'Munasinghe Dewage',
            'email' => 'kaviska525@gmail.com',
            'password' => 'Malidunew@123',
        ],);
    }
}
