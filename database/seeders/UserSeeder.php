<?php

namespace Database\Seeders;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $entreprise = Entreprise::firstOrCreate(['nom' => 'ENTREPRISE DAME'], ['actif' => true]);

        $donnees = DameDataFile::charger()['utilisateurs'] ?? [];

        foreach ($donnees as $ligne) {
            $username = $ligne["Nom d'utilisateur"] ?? null;
            $profil = $ligne['Profil'] ?? null;
            $motDePasse = $ligne['Mot de passe'] ?? null;

            // Ignore les lignes d'avertissement/informatives du classeur d'origine
            // qui ne correspondent pas a un vrai compte utilisateur.
            if (! $username || ! $profil || ! $motDePasse || ! in_array($profil, User::PROFILS, true)) {
                continue;
            }

            User::updateOrCreate(
                ['username' => $username],
                [
                    'entreprise_id' => $entreprise->id,
                    'name' => ucfirst($username),
                    'profil' => $profil,
                    'actif' => strtolower((string) ($ligne['Actif (Oui/Non)'] ?? 'Oui')) === 'oui',
                    'password' => Hash::make($motDePasse),
                    'derniere_connexion' => $ligne['Dernière connexion (auto)'] ?? null,
                ]
            );
        }

        // Compte administrateur de secours si le classeur n'en fournissait aucun.
        if (User::where('entreprise_id', $entreprise->id)->where('profil', 'Administrateur')->doesntExist()) {
            User::updateOrCreate(
                ['username' => 'admin'],
                [
                    'entreprise_id' => $entreprise->id,
                    'name' => 'Administrateur',
                    'profil' => 'Administrateur',
                    'actif' => true,
                    'password' => Hash::make('Admin2026!'),
                ]
            );
        }
    }
}
