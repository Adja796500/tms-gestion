<?php

namespace App\Livewire;

use App\Livewire\Concerns\GereDocumentsCommerciaux;
use App\Models\Facture;
use Livewire\Component;
use Livewire\WithPagination;

class BordereauxLivraison extends Component
{
    use WithPagination;
    use GereDocumentsCommerciaux;

    protected string $type = Facture::TYPE_BORDEREAU;
}
