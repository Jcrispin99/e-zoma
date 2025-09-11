<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Variant;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Jhamil Crispin',
            'email' => 'j99crispin@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $this->call([
            CategorySeder::class,
        ]);

        Product::factory(100)->create();
        Variant::factory(100)->create();
    }
}
