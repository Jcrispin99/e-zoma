<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class CentralDatabaseSeeder extends Seeder
{
    /**
     * Seed the central database.
     */
    public function run(): void
    {
        // Roles y permisos centrales + usuarios demo
        $this->call([
            RolesSeeder::class,
            ClientUserSeeder::class,
        ]);

        // Crear o actualizar usuario admin
        $email = env('SEED_ADMIN_EMAIL', 'admin@example.com');
        $password = env('SEED_ADMIN_PASSWORD', 'password');

        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => env('SEED_ADMIN_NAME', 'Admin Demo'),
                'email' => $email,
                'password' => bcrypt($password),
            ]);
        }

        // Asignar rol admin si disponible
        $role = Role::where('name', 'admin')->first();
        if ($role && !$user->hasRole('admin')) {
            $user->assignRole('admin');
        }
    }
}