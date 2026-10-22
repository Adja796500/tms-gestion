<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recu extends Model
{
    use AppartientAUneEntreprise;

    protected $table = 'recus';

    protected $fillable = [
        'entreprise_id',
        'numero',
        'paiement_client_id',
        'date',
        'montant',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function paiementClient(): BelongsTo
    {
        return $this->belongsTo(PaiementClient::class);
    }
}
