<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasFactory;
    use AppartientAUneEntreprise;

    protected $fillable = [
        'entreprise_id',
        'code',
        'code_barre',
        'designation',
        'photo_path',
        'categorie',
        'categorie_activite_id',
        'unite',
        'prix_achat',
        'prix_vente',
        'stock_initial',
        'stock_minimum',
        'stock_disponible',
    ];

    protected function casts(): array
    {
        return [
            'prix_achat' => 'decimal:2',
            'prix_vente' => 'decimal:2',
        ];
    }

    public function categorieActivite(): BelongsTo
    {
        return $this->belongsTo(CategorieActivite::class);
    }

    public function achats(): HasMany
    {
        return $this->hasMany(Achat::class);
    }

    public function venteLignes(): HasMany
    {
        return $this->hasMany(VenteLigne::class);
    }

    public function estEnAlerte(): bool
    {
        return $this->stock_disponible <= $this->stock_minimum;
    }

    /**
     * Recalcule et persiste le stock disponible = stock initial + achats cumules - ventes cumulees.
     */
    public function recalculerStock(): void
    {
        $achats = (int) $this->achats()->sum('quantite');
        $ventes = (int) $this->venteLignes()->sum('quantite');

        $this->stock_disponible = $this->stock_initial + $achats - $ventes;
        $this->save();
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }
}
