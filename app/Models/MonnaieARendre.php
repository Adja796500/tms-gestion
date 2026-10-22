<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUneEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonnaieARendre extends Model
{
    use HasFactory;
    use AppartientAUneEntreprise;

    protected $table = 'monnaies_a_rendre';

    protected $fillable = [
        'entreprise_id',
        'client_id',
        'vente_id',
        'date',
        'montant',
        'statut',
        'reglee_le',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'reglee_le' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function estReglee(): bool
    {
        return $this->statut === 'reglee';
    }
}
