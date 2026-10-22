<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactureLigne extends Model
{
    protected $table = 'facture_lignes';

    protected $fillable = [
        'facture_id',
        'designation',
        'quantite',
        'prix_unitaire',
        'total',
        'observations',
    ];

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }
}
