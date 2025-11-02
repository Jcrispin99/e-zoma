<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class ClientUserSeeder extends Seeder
{
    /**
     * Seed a demo client user in the central database.
     */
    public function run(): void
    {
        $email = env('SEED_CLIENT_EMAIL', 'cliente@example.com');
        $password = env('SEED_CLIENT_PASSWORD', 'password');
        $name = env('SEED_CLIENT_NAME', 'Cliente Demo');

        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
            ]);
        }

        // Assign a minimal role if available
        $role = Role::where('name', 'lector')->first();
        if ($role && !$user->hasRole('lector')) {
            $user->assignRole('lector');
        }
    }
}
