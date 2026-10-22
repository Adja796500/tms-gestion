<?php

namespace App\Support;

class CompteResolver
{
    /**
     * Centralise la regle "moyen de paiement -> compte", reutilisee par les modules
     * Comptabilite (ecritures de journal, acquisitions) et potentiellement les futurs
     * modules Tresorerie.
     */
    public static function depuisMoyenPaiement(string $moyen): string
    {
        return match ($moyen) {
            'Cheque', 'Virement', 'Mobile Money', 'Orange Money', 'Moov Money' => 'Banque',
            default => 'Caisse', // Especes
        };
    }
}
