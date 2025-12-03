<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Variant>
 */
class VariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'barcode' => $this->faker->unique()->ean13(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock' => $this->faker->numberBetween(0, 100),
            'is_principal' => false,
        ];
    }

    /**
     * Indicate that this variant should be principal.
     */
    public function principal(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_principal' => true,
        ]);
    }
}
