<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Variant;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Supplier;

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
            IdentitySeeder::class,
            CategorySeder::class,
            WarehouseSeeder::class,
            ReasonSeeder::class,
        ]);

        Customer::factory(50)->create();
        Supplier::factory(50)->create();
        Product::factory(100)->create();
        Variant::factory(100)->create();
    }
}
