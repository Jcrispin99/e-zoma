<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->paragraph(),
            'sku' => fake()->unique()->regexify('[A-Z]{3}[0-9]{5}'),
            'barcode' => fake()->unique()->ean13(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'category_id' => \App\Models\Category::inRandomOrder()->first()->id ?? 1,
        ];
    }
}
