<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ContratistaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user (not associated with any contratista)
        User::create([
            'name' => 'Administrador del Sistema',
            'email' => 'admin@portal.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'contratista_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create 5 contratistas with their respective users
        Contratista::factory(5)->create()->each(function (Contratista $contratista) {
            // Create contratista administrator
            User::create([
                'name' => 'Admin '.$contratista->razon_social,
                'email' => 'admin@'.strtolower(str_replace(' ', '', $contratista->nombre_fantasia)).'.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Contratista,
                'contratista_id' => $contratista->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Create 1-2 supervisors for each contratista
            $supervisorCount = rand(1, 2);
            for ($i = 1; $i <= $supervisorCount; $i++) {
                User::create([
                    'name' => 'Supervisor '.$i.' - '.$contratista->nombre_fantasia,
                    'email' => 'supervisor'.$i.'@'.strtolower(str_replace(' ', '', $contratista->nombre_fantasia)).'.com',
                    'password' => Hash::make('password'),
                    'role' => UserRole::Supervisor,
                    'contratista_id' => $contratista->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            }
        });

        $this->command->info('Created 1 admin user and '.Contratista::count().' contratistas with their users.');
    }
}
