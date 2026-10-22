<?php

namespace App\Support;

/**
 * Convertit un montant en toutes lettres francaises, pour les mentions du type
 * "Arrete le present document a la somme de : Trois millions cinq cent mille
 * francs CFA" sur les factures et recus imprimes.
 */
class NombreEnLettres
{
    private const UNITES = [
        '', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
        'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf',
    ];

    private const DIZAINES = [
        2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante',
    ];

    public static function convertir(int $nombre): string
    {
        if ($nombre === 0) {
            return 'zero';
        }

        if ($nombre < 0) {
            return 'moins '.self::convertir(-$nombre);
        }

        $milliards = intdiv($nombre, 1_000_000_000);
        $reste = $nombre % 1_000_000_000;
        $millions = intdiv($reste, 1_000_000);
        $reste = $reste % 1_000_000;
        $milliers = intdiv($reste, 1000);
        $unites = $reste % 1000;

        $parties = [];

        // "million"/"milliard" sont des noms (comme "euros") : cent/vingt gardent
        // leur s devant eux. "mille" est un adjectif numeral invariable : cent/vingt
        // perdent leur s devant lui. D'ou le second argument different ci-dessous.
        if ($milliards > 0) {
            $parties[] = self::centaines($milliards, true).' '.($milliards > 1 ? 'milliards' : 'milliard');
        }

        if ($millions > 0) {
            $parties[] = self::centaines($millions, true).' '.($millions > 1 ? 'millions' : 'million');
        }

        if ($milliers > 0) {
            $parties[] = $milliers === 1 ? 'mille' : self::centaines($milliers, false).' mille';
        }

        if ($unites > 0) {
            $parties[] = self::centaines($unites, true);
        }

        return implode(' ', $parties);
    }

    public static function enFrancsCfa(float $montant): string
    {
        $mot = self::convertir((int) round($montant));
        $mot = mb_strtoupper(mb_substr($mot, 0, 1)).mb_substr($mot, 1);

        return $mot.' francs CFA';
    }

    /**
     * Convertit un nombre de 0 a 999. $dernierGroupe indique si "cent" peut
     * prendre son s (uniquement quand rien, ou un nom comme "million(s)", ne
     * le suit — jamais devant "mille").
     */
    private static function centaines(int $n, bool $dernierGroupe): string
    {
        if ($n < 20) {
            return self::UNITES[$n];
        }

        if ($n < 100) {
            return self::dizaines($n, $dernierGroupe);
        }

        $centaines = intdiv($n, 100);
        $reste = $n % 100;
        $mot = $centaines === 1 ? 'cent' : self::UNITES[$centaines].' cent';

        if ($reste === 0) {
            return ($centaines > 1 && $dernierGroupe) ? $mot.'s' : $mot;
        }

        return $mot.' '.self::dizaines($reste, $dernierGroupe);
    }

    /**
     * Convertit un nombre de 0 a 99.
     */
    private static function dizaines(int $n, bool $dernierGroupe): string
    {
        if ($n < 20) {
            return self::UNITES[$n];
        }

        if ($n < 70) {
            $d = intdiv($n, 10);
            $u = $n % 10;

            if ($u === 0) {
                return self::DIZAINES[$d];
            }

            return $u === 1 ? self::DIZAINES[$d].'-et-un' : self::DIZAINES[$d].'-'.self::UNITES[$u];
        }

        if ($n < 80) {
            return $n === 71 ? 'soixante-et-onze' : 'soixante-'.self::UNITES[$n - 60];
        }

        if ($n === 80) {
            return $dernierGroupe ? 'quatre-vingts' : 'quatre-vingt';
        }

        return 'quatre-vingt-'.self::UNITES[$n - 80];
    }
}
