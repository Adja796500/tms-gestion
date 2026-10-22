<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;

class CreancesClients extends Component
{
    use WithPagination;

    public function render()
    {
        // Meme regle que Client::totalFacture() : ventes + factures definitives - paiements.
        $clients = Client::withSum('ventes as total_ventes', 'total')
            ->withSum(['factures as total_factures' => fn ($q) => $q->where('type', \App\Models\Facture::TYPE_DEFINITIVE)->where('statut', '!=', 'Annulée')], 'montant_total')
            ->withSum('paiements as total_paye', 'montant')
            ->orderBy('nom')
            ->paginate(15);

        $clients->getCollection()->transform(function (Client $client) {
            $client->total_facture = (float) ($client->total_ventes ?? 0) + (float) ($client->total_factures ?? 0);
            $client->reste = round($client->total_facture - (float) ($client->total_paye ?? 0), 2);

            return $client;
        });

        return view('livewire.creances-clients', ['clients' => $clients])
            ->layout('layouts.app', ['title' => 'Créances clients']);
    }
}
