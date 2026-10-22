<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facture extends Model
{
    use HasFactory;
    use AppartientAUneEntreprise;

    public const TYPE_PROFORMA = 'proforma';
    public const TYPE_DEFINITIVE = 'definitive';
    public const TYPE_BORDEREAU = 'bordereau';
    public const TYPE_BON_COMMANDE = 'bon_commande';

    /**
     * Libelles du REGISTRE_DOCUMENTS du classeur.
     */
    public const LIBELLES_TYPE = [
        self::TYPE_PROFORMA => 'Proforma',
        self::TYPE_DEFINITIVE => 'Facture',
        self::TYPE_BORDEREAU => 'Bordereau',
        self::TYPE_BON_COMMANDE => 'Bon de commande',
    ];

    /**
     * Liste "Statuts des documents commerciaux" de la feuille PARAMETRES.
     */
    public const STATUTS = ['Emise', 'Envoyée', 'Acceptée', 'Payée', 'Non payée', 'Livré', 'En attente', 'Annulée'];

    protected $fillable = [
        'entreprise_id',
        'type',
        'numero',
        'client_id',
        'fournisseur_id',
        'facture_liee_id',
        'objet',
        'date',
        'montant_total',
        'remise_pourcentage',
        'statut',
        'envoyee_le',
        'derniere_relance_le',
        'nb_relances',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'envoyee_le' => 'datetime',
            'derniere_relance_le' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function factureLiee(): BelongsTo
    {
        return $this->belongsTo(self::class, 'facture_liee_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(FactureLigne::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementClient::class);
    }

    public function libelleType(): string
    {
        return self::LIBELLES_TYPE[$this->type] ?? $this->type;
    }

    /**
     * Client ou fournisseur selon le type (le bon de commande vise un fournisseur).
     */
    public function nomPartenaire(): string
    {
        return $this->type === self::TYPE_BON_COMMANDE
            ? ($this->fournisseur->nom ?? '—')
            : ($this->client->nom ?? '—');
    }

    /**
     * "Designation resumee" du registre : lignes jointes par " + ", 60 caracteres max.
     */
    public function designationResumee(): string
    {
        return \Illuminate\Support\Str::limit($this->lignes->pluck('designation')->implode(' + '), 57);
    }
}
