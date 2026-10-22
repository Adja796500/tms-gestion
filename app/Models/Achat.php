<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achat extends Model
{
    use HasFactory;
    use AppartientAUneEntreprise;

    protected $fillable = [
        'entreprise_id',
        'numero',
        'date',
        'article_id',
        'fournisseur_id',
        'quantite',
        'prix_unitaire',
        'total',
        'mode_reglement',
        'montant_regle',
        'solde_restant',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementFournisseur::class);
    }

    public function rafraichirStatut(): void
    {
        $this->solde_restant = round((float) $this->total - (float) $this->montant_regle, 2);
        $this->statut = $this->solde_restant <= 0
            ? ($this->mode_reglement === 'Comptant' ? 'Paye (comptant)' : 'Paye')
            : ($this->montant_regle > 0 ? 'Partiellement regle' : 'Impaye');
        $this->save();
    }
}
