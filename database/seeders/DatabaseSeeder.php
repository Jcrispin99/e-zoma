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


        $this->call([
            UbigeoDepartmentSeeder::class,
            UbigeoProvinceSeeder::class,
            UbigeoDistrictSeeder::class,
            IdentitySeeder::class,
            CompanySeeder::class,
            CategorySeder::class,
            WarehouseSeeder::class,
            ReasonSeeder::class,
            AttributeSeeder::class,
            RolesSeeder::class,
            SequenceSeeder::class,
            PaymentMethodSeeder::class,
            LoyaltyProgramSeeder::class,
            LoyaltyEarnRulesSeeder::class,
            LoyaltyRewardsSeeder::class,
        ]);

        Customer::factory(50)->create();
        Supplier::factory(50)->create();
        Product::factory(100)->create();
        Variant::factory(100)->create();
    }
}
