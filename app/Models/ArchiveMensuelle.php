<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveMensuelle extends Model
{
    use AppartientAUneEntreprise;

    protected $table = 'archives_mensuelles';

    protected $fillable = [
        'entreprise_id',
        'mois',
        'nb_factures',
        'chiffre_affaires',
        'depenses',
        'benefice',
        'utilisateur_id',
    ];

    protected function casts(): array
    {
        return [
            'mois' => 'date',
            'chiffre_affaires' => 'decimal:2',
            'depenses' => 'decimal:2',
            'benefice' => 'decimal:2',
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
