<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;
    use AppartientAUneEntreprise;

    protected $fillable = ['entreprise_id', 'nom', 'telephone', 'email', 'adresse'];

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementClient::class);
    }

    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }

    /**
     * Tout ce qui a ete facture au client : ses ventes ET ses factures definitives
     * (hors annulees). Chaque paiement (lie a une vente, a une facture ou a rien)
     * vient ensuite en deduction : sans les factures, un paiement de facture rendait
     * le solde du client negatif.
     */
    public function totalFacture(): float
    {
        return (float) $this->ventes()->sum('total')
            + (float) $this->factures()->where('type', Facture::TYPE_DEFINITIVE)->where('statut', '!=', 'Annulée')->sum('montant_total');
    }

    public function totalPaye(): float
    {
        return (float) $this->paiements()->sum('montant');
    }

    public function resteAPayer(): float
    {
        return round($this->totalFacture() - $this->totalPaye(), 2);
    }
}
