<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fournisseur extends Model
{
    use HasFactory;
    use AppartientAUneEntreprise;

    protected $fillable = ['entreprise_id', 'nom', 'telephone', 'email', 'adresse'];

    public function achats(): HasMany
    {
        return $this->hasMany(Achat::class);
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementFournisseur::class);
    }

    public function totalAchete(): float
    {
        return (float) $this->achats()->sum('total');
    }

    public function totalRegle(): float
    {
        return (float) $this->reglements()->sum('montant');
    }

    public function soldeRestant(): float
    {
        return round($this->totalAchete() - $this->totalRegle(), 2);
    }
}
