<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IdentitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $identities = [
            'Sin documento',
            'DNI',
            'RUC',
            'Carnet de Extranjería',
            'Pasaporte',
            'Otro',
        ];

        foreach ($identities as $identity) {
            \App\Models\Identity::create([
                'name' => $identity,
            ]);
        }
    }
}
