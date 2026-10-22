<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaiementClient extends Model
{
    use HasFactory;
    use AppartientAUneEntreprise;

    protected $table = 'paiements_clients';

    protected $fillable = [
        'entreprise_id',
        'numero',
        'date',
        'client_id',
        'vente_id',
        'facture_id',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function recu(): HasOne
    {
        return $this->hasOne(Recu::class);
    }
}
