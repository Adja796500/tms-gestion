<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Entreprise;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $entreprise = Entreprise::firstOrCreate(['nom' => 'ENTREPRISE DAME'], ['actif' => true]);

        $donnees = DameDataFile::charger()['clients'] ?? [];

        foreach ($donnees as $ligne) {
            $nom = $ligne['Nom / Raison sociale'] ?? null;

            if (! $nom) {
                continue;
            }

            Client::updateOrCreate(
                ['nom' => $nom, 'entreprise_id' => $entreprise->id],
                [
                    'telephone' => isset($ligne['Téléphone']) ? (string) $ligne['Téléphone'] : null,
                    'email' => $ligne['Email'] ?? null,
                    'adresse' => $ligne['Adresse'] ?? null,
                ]
            );
        }
    }
}
