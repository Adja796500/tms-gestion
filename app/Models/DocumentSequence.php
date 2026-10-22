<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    use AppartientAUneEntreprise;

    protected $fillable = [
        'entreprise_id',
        'table_name',
        'annee',
        'dernier_numero',
    ];

    protected function casts(): array
    {
        return [
            'annee' => 'integer',
            'dernier_numero' => 'integer',
        ];
    }
}
