<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Electrónica' => ['Teléfonos', 'Laptops', 'Televisores'],
            'Ropa' => ['Hombres', 'Mujeres', 'Niños'],
            'Hogar y Jardín' => ['Muebles', 'Decoración', 'Jardinería'],
        ];

        foreach ($categories as $parentName => $children) {
            $parent = Category::create([
                'name' => $parentName,
                'slug' => Str::slug($parentName),
                'description' => 'Descripción de la categoría ' . $parentName,
            ]);

            foreach ($children as $childName) {
                Category::create([
                    'name' => $childName,
                    'slug' => Str::slug($childName),
                    'parent_id' => $parent->id,
                    'full_name' => $parent->name . ' > ' . $childName,
                    'description' => 'Descripción de la subcategoría ' . $childName,
                ]);
            }
        }
    }
}
