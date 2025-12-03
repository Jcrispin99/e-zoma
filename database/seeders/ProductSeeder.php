<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        $colorAttribute = Attribute::where('name', 'Color')->first();
        $sizeAttribute = Attribute::where('name', 'Talla')->first();

        if ($categories->isEmpty()) {
            $this->command->warn('No hay categorías. Por favor ejecute CategorySeeder primero.');
            return;
        }

        Product::factory(100)->create()->each(function ($product) use ($colorAttribute, $sizeAttribute, $categories) {
            $product->category_id = $categories->random()->id;
            $product->save();

            $hasVariants = rand(1, 100) <= 70;

            if ($hasVariants && ($colorAttribute || $sizeAttribute)) {
                $attribute = rand(0, 1) === 0 ? $colorAttribute : $sizeAttribute;

                if (!$attribute) {
                    $attribute = $colorAttribute ?? $sizeAttribute;
                }

                if ($attribute) {
                    $attributeValues = $attribute->attributeValues;

                    if ($attributeValues->isEmpty()) {
                        $product->variants()->create([
                            'sku' => 'SKU-' . strtoupper(uniqid()),
                            'barcode' => fake()->unique()->ean13(),
                            'price' => $product->price,
                            'stock' => rand(0, 100),
                            'is_principal' => true,
                        ]);
                    } else {
                        $selectedValues = $attributeValues->random(min(rand(2, 4), $attributeValues->count()));

                        $selectedValues->each(function ($attributeValue, $index) use ($product, $attribute) {
                            $variant = $product->variants()->create([
                                'sku' => 'SKU-' . strtoupper(uniqid()),
                                'barcode' => fake()->unique()->ean13(),
                                'price' => $product->price + rand(-10, 20),
                                'stock' => rand(0, 100),
                                'is_principal' => $index === 0,
                            ]);

                            $variant->attributeValues()->attach($attributeValue->id);
                        });
                    }
                }
            } else {
                $product->variants()->create([
                    'sku' => 'SKU-' . strtoupper(uniqid()),
                    'barcode' => fake()->unique()->ean13(),
                    'price' => $product->price,
                    'stock' => rand(0, 100),
                    'is_principal' => true,
                ]);
            }
        });

        $this->command->info('Se crearon 100 productos con variantes correctamente.');
    }
}

