<?php

namespace Database\Seeders;

use App\Models\Variation;
use App\Models\VariationOption;
use Illuminate\Database\Seeder;

class VariationOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $variations = Variation::all();

        if ($variations->isEmpty()) {
            return;
        }

        $optionSets = [
            'color' => ['Red', 'Blue', 'Green', 'Black'],
            'size' => ['S', 'M', 'L', 'XL'],
        ];

        $variations->each(function (Variation $variation) use ($optionSets): void {
            $key = strtolower($variation->name);
            $options = $optionSets[$key] ?? ['Standard'];

            foreach ($options as $index => $optionName) {
                VariationOption::factory()->create([
                    'variation_id' => $variation->id,
                    'name' => $optionName,
                    'value' => $key === 'color' ? strtolower($optionName) : $optionName,
                    'display_order' => $index + 1,
                    'is_default' => $index === 0,
                    'additional_price' => $index > 1 ? $index * 3 : 0,
                    'metadata' => [
                        'label' => $optionName,
                    ],
                ]);
            }
        });
    }
}
