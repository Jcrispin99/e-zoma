<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the tenant's database.
     */
    public function run(): void
    {
        // Seed mínimo para operar en el tenant
        $this->call([
            IdentitySeeder::class,
            CompanySeeder::class,
        ]);

        // Vincular usuario central al company principal del tenant
        $company = Company::query()->first();
        if ($company) {
            $user = User::query()->where('email', env('SEED_ADMIN_EMAIL', 'admin@example.com'))->first();
            if ($user) {
                // Attach manually on tenant connection to ensure correct pivot DB
                \Illuminate\Support\Facades\DB::connection('tenant')
                    ->table('company_user')
                    ->updateOrInsert(
                        [
                            'company_id' => $company->id,
                            'user_id' => $user->id,
                        ],
                        [
                            'is_default' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
            }

            // Attach demo client user if present
            $clientUser = User::query()->where('email', env('SEED_CLIENT_EMAIL', 'cliente@example.com'))->first();
            if ($clientUser) {
                \Illuminate\Support\Facades\DB::connection('tenant')
                    ->table('company_user')
                    ->updateOrInsert(
                        [
                            'company_id' => $company->id,
                            'user_id' => $clientUser->id,
                        ],
                        [
                            'is_default' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
            }
        }
    }
}