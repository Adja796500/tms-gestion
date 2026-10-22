<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vente extends Model
{
    use HasFactory;
    use AppartientAUneEntreprise;

    protected $fillable = [
        'entreprise_id',
        'numero',
        'date',
        'client_id',
        'total',
        'remise',
        'remise_pourcentage',
        'mode_paiement',
        'montant_remis',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(VenteLigne::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementClient::class);
    }

    public function monnaiesARendre(): HasMany
    {
        return $this->hasMany(MonnaieARendre::class);
    }
}
