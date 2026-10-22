<?php

namespace Database\Seeders;

use App\Models\Entreprise;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Ordre d'execution important : les tables de reference d'abord,
     * puis les mouvements qui en dependent.
     */
    public function run(): void
    {
        $entreprise = Entreprise::firstOrCreate(['nom' => 'ENTREPRISE DAME'], ['actif' => true]);

        $this->callWith(AccessRuleSeeder::class, ['entrepriseId' => $entreprise->id]);

        $this->call([
            SuperAdminSeeder::class,
            UserSeeder::class,
            ClientSeeder::class,
            FournisseurSeeder::class,
            ArticleSeeder::class,
            AchatSeeder::class,
            VenteSeeder::class,
        ]);
    }
}
