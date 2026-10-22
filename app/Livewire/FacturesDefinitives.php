<?php

namespace App\Livewire;

use App\Livewire\Concerns\GereFactures;
use App\Models\Facture;
use Livewire\Component;
use Livewire\WithPagination;

class FacturesDefinitives extends Component
{
    use WithPagination;
    use GereFactures;

    protected string $type = Facture::TYPE_DEFINITIVE;
}
