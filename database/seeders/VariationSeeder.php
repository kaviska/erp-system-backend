<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Variation;
use Illuminate\Database\Seeder;

class VariationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            return;
        }

        $templateVariations = [
            ['name' => 'Color', 'is_required' => true],
            ['name' => 'Size', 'is_required' => true],
        ];

        $products->each(function (Product $product) use ($templateVariations): void {
            foreach ($templateVariations as $index => $template) {
                Variation::factory()->create([
                    'product_id' => $product->id,
                    'name' => $template['name'],
                    'is_required' => $template['is_required'],
                    'display_order' => $index + 1,
                ]);
            }
        });
    }
}
