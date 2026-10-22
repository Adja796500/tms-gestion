<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * N'appartient a aucune entreprise (entreprise_id null) : gere uniquement
     * la liste des entreprises clientes via /super-admin/entreprises.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'entreprise_id' => null,
                'name' => 'Super Administrateur',
                'profil' => 'Super Administrateur',
                'actif' => true,
                'est_super_administrateur' => true,
                'password' => Hash::make('SuperAdmin2026!'),
            ]
        );
    }
}
