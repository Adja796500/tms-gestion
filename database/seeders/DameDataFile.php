<?php

namespace Database\Seeders;

class DameDataFile
{
    /**
     * Charge les donnees reelles extraites du classeur Excel d'origine
     * (ENTREPRISE_DAME_ODG_v33b_SYNTHESE_v2_1.xlsm) au format JSON.
     */
    public static function charger(): array
    {
        $chemin = __DIR__.'/data/seed_data.json';

        if (! file_exists($chemin)) {
            return [];
        }

        return json_decode(file_get_contents($chemin), true) ?? [];
    }
}
