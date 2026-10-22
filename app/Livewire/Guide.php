<?php

namespace App\Livewire;

use Livewire\Component;

class Guide extends Component
{
    public function render()
    {
        return view('livewire.guide')->layout('layouts.app', ['title' => "Guide d'utilisation"]);
    }
}
