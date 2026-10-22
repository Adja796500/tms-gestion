<?php

namespace Tests\Unit;

use App\Support\NombreEnLettres;
use Tests\TestCase;

class NombreEnLettresTest extends TestCase
{
    /**
     * @dataProvider nombres
     */
    public function test_conversion_en_lettres(int $nombre, string $attendu): void
    {
        $this->assertEquals($attendu, NombreEnLettres::convertir($nombre));
    }

    public static function nombres(): array
    {
        return [
            [0, 'zero'],
            [1, 'un'],
            [15, 'quinze'],
            [17, 'dix-sept'],
            [20, 'vingt'],
            [21, 'vingt-et-un'],
            [22, 'vingt-deux'],
            [61, 'soixante-et-un'],
            [70, 'soixante-dix'],
            [71, 'soixante-et-onze'],
            [79, 'soixante-dix-neuf'],
            [80, 'quatre-vingts'],
            [81, 'quatre-vingt-un'],
            [90, 'quatre-vingt-dix'],
            [91, 'quatre-vingt-onze'],
            [99, 'quatre-vingt-dix-neuf'],
            [100, 'cent'],
            [101, 'cent un'],
            [180, 'cent quatre-vingts'],
            [200, 'deux cents'],
            [201, 'deux cent un'],
            [999, 'neuf cent quatre-vingt-dix-neuf'],
            [1000, 'mille'],
            [1001, 'mille un'],
            [1980, 'mille neuf cent quatre-vingts'],
            [80000, 'quatre-vingt mille'],
            [200000, 'deux cent mille'],
            [1000000, 'un million'],
            [2000000, 'deux millions'],
            [80000000, 'quatre-vingts millions'],
            [1000000000, 'un milliard'],
            [3500000, 'trois millions cinq cent mille'],
        ];
    }

    public function test_en_francs_cfa_capitalise_et_ajoute_le_suffixe(): void
    {
        $this->assertEquals('Trois millions cinq cent mille francs CFA', NombreEnLettres::enFrancsCfa(3500000));
        $this->assertEquals('Cent francs CFA', NombreEnLettres::enFrancsCfa(100));
    }
}
