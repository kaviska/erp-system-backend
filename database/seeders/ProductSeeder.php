<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sellers = Seller::all();
        $categories = Category::with('subCategories')->get();

        if ($sellers->isEmpty() || $categories->isEmpty()) {
            return;
        }

        $sellers->each(function (Seller $seller) use ($categories): void {
            $productCount = rand(3, 6);

            for ($i = 0; $i < $productCount; $i++) {
                $category = $categories->random();
                $subCategory = $category->subCategories->isNotEmpty()
                    ? $category->subCategories->random()
                    : null;

                Product::factory()->create([
                    'seller_id' => $seller->id,
                    'category_id' => $category->id,
                    'sub_category_id' => $subCategory?->id,
                    'is_published' => true,
                    'status' => 'active',
                ]);
            }
        });
    }
}
