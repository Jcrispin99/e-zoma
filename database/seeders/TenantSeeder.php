<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;

class TenantSeeder extends Seeder
{
    /**
     * Create a demo tenant with domain and prepare it for use.
     */
    public function run(): void
    {
        $id = env('SEED_TENANT_ID', 'cliente_demo');
        $domainBase = config('app.domain');

        $tenant = Tenant::query()->firstOrCreate(['id' => $id]);
        $tenant->domains()->firstOrCreate([
            'domain' => $id . '.' . $domainBase,
        ]);
    }
}
