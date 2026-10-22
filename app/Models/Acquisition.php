<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Acquisition extends Model
{
    use AppartientAUneEntreprise;

    protected $fillable = [
        'entreprise_id',
        'date',
        'type',
        'designation',
        'montant',
        'fournisseur_id',
        'moyen_paiement',
        'compte',
        'utilisateur_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'montant' => 'decimal:2',
        ];
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
