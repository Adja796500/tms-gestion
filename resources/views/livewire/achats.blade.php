<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Rechercher un achat (numéro ou article)..."
               class="w-full sm:w-80 input-field">
        @if (auth()->user()->peutModifier('ACHATS_STOCK'))
            <button wire:click="creer" class="btn-primary">
                + Nouvel achat
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">Nouvel achat</h2>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label-field">Date</label>
                    <input type="date" wire:model="date" class="mt-1 w-full input-field">
                    @error('date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Article</label>
                    <select wire:model="article_id" class="mt-1 w-full input-field">
                        <option value="">-- Choisir --</option>
                        @foreach ($articlesDisponibles as $article)
                            <option value="{{ $article->id }}">{{ $article->code }} — {{ $article->designation }}</option>
                        @endforeach
                    </select>
                    @error('article_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Fournisseur</label>
                    <select wire:model="fournisseur_id" class="mt-1 w-full input-field">
                        <option value="">-- Aucun --</option>
                        @foreach ($fournisseursDisponibles as $fournisseur)
                            <option value="{{ $fournisseur->id }}">{{ $fournisseur->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-field">Quantité</label>
                    <input type="number" wire:model="quantite" class="mt-1 w-full input-field">
                    @error('quantite') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Prix d'achat unitaire</label>
                    <input type="number" step="0.01" wire:model="prix_unitaire" class="mt-1 w-full input-field">
                    @error('prix_unitaire') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Mode de règlement</label>
                    <select wire:model.live="mode_reglement" class="mt-1 w-full input-field">
                        <option value="Comptant">Comptant</option>
                        <option value="Credit">Credit</option>
                    </select>
                </div>
                @if ($mode_reglement === 'Credit')
                    <div>
                        <label class="label-field">Montant déjà réglé</label>
                        <input type="number" step="0.01" wire:model="montant_regle" class="mt-1 w-full input-field">
                    </div>
                @endif

                <div class="sm:col-span-3 flex gap-3 pt-2">
                    <x-btn-submit>Enregistrer</x-btn-submit>
                    <button type="button" wire:click="annuler" class="btn-ghost">Annuler</button>
                </div>
            </form>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">N°</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Article</th>
                    <th class="px-4 py-3 text-left">Fournisseur</th>
                    <th class="px-4 py-3 text-right">Qte</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-left">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($achats as $achat)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $achat->numero }}</td>
                        <td class="px-4 py-3">{{ $achat->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $achat->article->designation ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $achat->fournisseur->nom ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ $achat->quantite }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($achat->total, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs">{{ $achat->statut }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if (auth()->user()->peutModifier('ACHATS_STOCK'))
                                <x-action-delete call="supprimer({{ $achat->id }})" label="l'achat {{ $achat->numero }}" />
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="8" message="Aucun achat." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $achats->links() }}</div>
    </div>
</div>
