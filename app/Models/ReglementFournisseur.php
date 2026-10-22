<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglementFournisseur extends Model
{
    use HasFactory;
    use AppartientAUneEntreprise;

    protected $table = 'reglements_fournisseurs';

    protected $fillable = [
        'entreprise_id',
        'numero',
        'date',
        'fournisseur_id',
        'achat_id',
        'montant',
        'mode_paiement',
        'observations',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function achat(): BelongsTo
    {
        return $this->belongsTo(Achat::class);
    }
}
