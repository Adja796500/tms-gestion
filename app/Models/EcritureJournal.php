<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcritureJournal extends Model
{
    use AppartientAUneEntreprise;

    protected $table = 'ecritures_journal';

    protected $fillable = [
        'entreprise_id',
        'date',
        'categorie_activite_id',
        'type',
        'description',
        'montant',
        'moyen_paiement',
        'compte',
        'utilisateur_id',
        'vente_id',
    ];

    /**
     * Libelles affiches, comme dans le classeur (Entree/Depense sont stockes sans
     * accent en base pour rester compatibles avec l'enum existant).
     */
    public const LIBELLES_TYPE = ['Entree' => 'Entrée', 'Depense' => 'Dépense'];

    public const MOYENS_PAIEMENT = ['Especes' => 'Espèces', 'Cheque' => 'Chèque', 'Virement' => 'Virement', 'Mobile Money' => 'Mobile Money', 'Orange Money' => 'Orange Money', 'Moov Money' => 'Moov Money'];

    public function libelleType(): string
    {
        return self::LIBELLES_TYPE[$this->type] ?? $this->type;
    }

    public function libelleMoyenPaiement(): string
    {
        return self::MOYENS_PAIEMENT[$this->moyen_paiement] ?? $this->moyen_paiement;
    }

    /**
     * Colonne "Mois (auto)" du JOURNAL : "Juillet 2026".
     */
    public function mois(): string
    {
        return ucfirst($this->date->locale('fr')->translatedFormat('F Y'));
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'montant' => 'decimal:2',
        ];
    }

    public function categorieActivite(): BelongsTo
    {
        return $this->belongsTo(CategorieActivite::class);
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /**
     * Filtres du Journal des transactions, partages par l'ecran et l'export Excel.
     *
     * @param  array{du?: ?string, au?: ?string, categorie?: ?string, type?: ?string, compte?: ?string, recherche?: ?string}  $filtres
     */
    public function scopeFiltrer(Builder $query, array $filtres): Builder
    {
        return $query
            ->when($filtres['du'] ?? null, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($filtres['au'] ?? null, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($filtres['categorie'] ?? null, fn ($q, $v) => $q->where('categorie_activite_id', $v))
            ->when($filtres['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filtres['compte'] ?? null, fn ($q, $v) => $q->where('compte', $v))
            ->when(trim((string) ($filtres['recherche'] ?? '')) !== '', fn ($q) => $q->where('description', 'ilike', '%'.trim($filtres['recherche']).'%'));
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }
}
