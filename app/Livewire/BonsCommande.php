<?php

namespace App\Livewire;

use App\Livewire\Concerns\GereDocumentsCommerciaux;
use App\Models\Facture;
use Livewire\Component;
use Livewire\WithPagination;

class BonsCommande extends Component
{
    use WithPagination;
    use GereDocumentsCommerciaux;

    protected string $type = Facture::TYPE_BON_COMMANDE;
}
