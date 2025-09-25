<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attribute;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributes = [
            ['name' => 'Color'],
            ['name' => 'Talla'],
            ['name' => 'Material'],
            ['name' => 'Estilo'],
        ];

        foreach ($attributes as $attr) {
            Attribute::create($attr);
        }
    }
}
