<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commande extends Model
{
    use AppartientAUneEntreprise;

    protected $fillable = [
        'entreprise_id',
        'numero',
        'client_id',
        'date',
        'designation',
        'quantite',
        'montant',
        'date_livraison_prevue',
        'statut',
        'utilisateur_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'date_livraison_prevue' => 'date',
            'montant' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
