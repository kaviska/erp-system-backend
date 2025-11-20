<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();

        if ($categories->isEmpty()) {
            return;
        }

        $categories->each(function (Category $category): void {
            SubCategory::factory()
                ->count(3)
                ->create([
                    'category_id' => $category->id,
                ]);
        });
    }
}
