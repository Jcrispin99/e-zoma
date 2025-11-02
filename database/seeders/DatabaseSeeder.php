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
     * Seed the application's central database.
     */
    public function run(): void
    {
        $this->call([
            CentralDatabaseSeeder::class,
        ]);
    }
}
