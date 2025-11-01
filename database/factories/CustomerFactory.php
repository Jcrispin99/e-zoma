<?php

namespace Database\Factories;

use App\Models\Identity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identity_id' => 4,
            'document_number' => $this->faker->unique()->numerify('###########'),
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
        ];
    }

    /**
     * Estado para DNI con dígitos de puro cero (8 dígitos todos cero)
     */
    public function dniZero(): Factory
    {
        return $this->state(function () {
            $dniId = Identity::where('name', 'DNI')->value('id') ?? 2;
            return [
                'identity_id' => $dniId,
                'document_number' => str_repeat('0', 8),
            ];
        });
    }

    /**
     * Estado para RUC con dígitos de puro cero (11 dígitos todos cero)
     */
    public function rucZero(): Factory
    {
        return $this->state(function () {
            $rucId = Identity::where('name', 'RUC')->value('id') ?? 3;
            return [
                'identity_id' => $rucId,
                'document_number' => str_repeat('0', 11),
            ];
        });
    }

    /**
     * Estado para DNI con ceros a la izquierda (8 dígitos, padding de ceros)
     */
    public function dniPaddedZeros(): Factory
    {
        return $this->state(function () {
            $dniId = Identity::where('name', 'DNI')->value('id') ?? 2;
            $num = $this->faker->numberBetween(0, 99999999);
            return [
                'identity_id' => $dniId,
                'document_number' => str_pad((string) $num, 8, '0', STR_PAD_LEFT),
            ];
        });
    }

    /**
     * Estado para RUC con ceros a la izquierda (11 dígitos, padding de ceros)
     */
    public function rucPaddedZeros(): Factory
    {
        return $this->state(function () {
            $rucId = Identity::where('name', 'RUC')->value('id') ?? 3;
            $num = $this->faker->numberBetween(0, 99999999999);
            return [
                'identity_id' => $rucId,
                'document_number' => str_pad((string) $num, 11, '0', STR_PAD_LEFT),
            ];
        });
    }
}
