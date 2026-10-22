<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\PaiementClient;
use App\Models\Vente;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class HistoriqueVentes extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('VENTES'), 403);

        DB::transaction(function () use ($id) {
            $vente = Vente::with('lignes')->findOrFail($id);
            $articleIds = $vente->lignes->pluck('article_id')->unique();

            // Le paiement lie a cette vente (encaissement automatique au comptoir,
            // voir PaiementVenteSynchroniseur) n'a plus de sens une fois la vente
            // supprimee : sans ca il resterait dans Paiements clients et fausserait
            // le solde du client (paiement sans vente correspondante).
            PaiementClient::where('vente_id', $vente->id)->delete();

            $vente->delete();

            foreach (Article::whereIn('id', $articleIds)->get() as $article) {
                $article->recalculerStock();
            }
        });

        session()->flash('status', 'Vente supprimée.');
    }

    public function render()
    {
        $ventes = Vente::query()
            ->with(['client', 'lignes.article'])
            ->when($this->search, function ($query) {
                $query->where('numero', 'ilike', "%{$this->search}%")
                    ->orWhereHas('client', fn ($q) => $q->where('nom', 'ilike', "%{$this->search}%"));
            })
            ->latest('date')
            ->latest('id')
            ->paginate(15);

        return view('livewire.historique-ventes', [
            'ventes' => $ventes,
        ])->layout('layouts.app', ['title' => 'Historique des ventes']);
    }
}
