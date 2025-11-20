<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\VariationOption;
use App\Models\VariationStock;
use Illuminate\Database\Seeder;

class VariationStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::with(['variations.options'])->get();

        if ($products->isEmpty()) {
            return;
        }

        $products->each(function (Product $product): void {
            // Group options by variation
            $variationOptions = $product->variations
                ->filter(fn ($variation) => $variation->options->isNotEmpty())
                ->mapWithKeys(fn ($variation) => [
                    $variation->id => $variation->options->take(3)->pluck('id')->toArray()
                ])
                ->toArray();

            // Generate combinations of variation option IDs
            $combinations = $this->cartesianProductOfIds($variationOptions);

            if (empty($combinations)) {
                // No variations, create a simple stock entry
                $quantity = max(1, (int) $product->stock_quantity);

                $stock = VariationStock::factory()->create([
                    'product_id' => $product->id,
                    'price' => $product->sale_price ?? $product->price,
                    'quantity' => $quantity,
                    'reserved_quantity' => rand(0, $quantity),
                    'option_values' => null,
                ]);

                return;
            }

            // Create stock entries for each combination (e.g., Red + XL)
            collect($combinations)
                ->take(8) // Limit to 8 combinations per product
                ->each(function (array $optionIds) use ($product): void {
                    $quantity = rand(5, 25);
                    $options = VariationOption::with('variation')->whereIn('id', $optionIds)->get();

                    // Build option_values for display
                    $optionValues = $options->mapWithKeys(function ($option) {
                        return [$option->variation->name => $option->name];
                    })->toArray();

                    // Create the stock entry
                    $stock = VariationStock::factory()->create([
                        'product_id' => $product->id,
                        'price' => $product->sale_price ?? $product->price,
                        'quantity' => $quantity,
                        'reserved_quantity' => rand(0, min(5, $quantity)),
                        'option_values' => $optionValues,
                        'image_path' => 'products/variations/' . strtolower(str_replace(' ', '-', $product->name)) . '-' . strtolower(str_replace(' ', '-', implode('-', $options->pluck('value')->toArray()))) . '.jpg',
                    ]);

                    // Attach multiple variation options (e.g., Red color option + XL size option)
                    $stock->variationOptions()->attach($optionIds);
                });
        });
    }

    /**
     * Generate cartesian product of variation option IDs
     * Example: [1,2] x [3,4] = [[1,3], [1,4], [2,3], [2,4]]
     * 
     * @param array<int, array<int>> $variationOptions Array of variation_id => [option_ids]
     * @return array<int, array<int>> Array of combinations, each containing option IDs
     */
    private function cartesianProductOfIds(array $variationOptions): array
    {
        if (empty($variationOptions)) {
            return [];
        }

        $result = [[]];

        foreach ($variationOptions as $optionIds) {
            $append = [];

            foreach ($result as $combination) {
                foreach ($optionIds as $optionId) {
                    $append[] = array_merge($combination, [$optionId]);
                }
            }

            $result = $append;
        }

        return $result;
    }
}
