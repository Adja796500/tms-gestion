<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Model;

class ParametreEntreprise extends Model
{
    use AppartientAUneEntreprise;

    protected $table = 'parametres_entreprise';

    protected $fillable = [
        'entreprise_id',
        'nom_entreprise',
        'sigle',
        'logo_path',
        'activites_texte',
        'telephone',
        'email',
        'ville',
        'adresse',
        'regime_fiscal',
        'division_fiscale',
        'rccm',
        'ifu',
        'signataire',
        'taux_investissement',
        'taux_epargne',
        'taux_loyer',
        'taux_fonctionnement',
        'solde_initial_caisse',
        'solde_initial_banque',
        'envoi_auto_factures',
        'relances_actives',
        'relance_delai_jours',
        'relance_intervalle_jours',
    ];

    protected function casts(): array
    {
        return [
            'taux_investissement' => 'decimal:2',
            'taux_epargne' => 'decimal:2',
            'taux_loyer' => 'decimal:2',
            'taux_fonctionnement' => 'decimal:2',
            'solde_initial_caisse' => 'decimal:2',
            'solde_initial_banque' => 'decimal:2',
            'envoi_auto_factures' => 'boolean',
            'relances_actives' => 'boolean',
        ];
    }

    public function logoUrl(): ?string
    {
        // asset() suit l'adresse reellement utilisee (port, domaine), contrairement a APP_URL.
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }
}
