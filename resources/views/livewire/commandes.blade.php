<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Rechercher une commande (numéro ou client)..."
               class="w-full sm:w-80 input-field">
        @if (auth()->user()->peutModifier('COMMANDES'))
            <button wire:click="creer" class="btn-primary">
                + Nouvelle commande
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">Nouvelle commande</h2>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label-field">Client</label>
                    <select wire:model="client_id" class="mt-1 w-full input-field">
                        <option value="">-- Choisir --</option>
                        @foreach ($clientsDisponibles as $client)
                            <option value="{{ $client->id }}">{{ $client->nom }}</option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Date</label>
                    <input type="date" wire:model="date" class="mt-1 w-full input-field">
                    @error('date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Date livraison prévue</label>
                    <input type="date" wire:model="date_livraison_prevue" class="mt-1 w-full input-field">
                </div>
                <div class="sm:col-span-2">
                    <label class="label-field">Désignation</label>
                    <input type="text" wire:model="designation" class="mt-1 w-full input-field">
                    @error('designation') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Quantité</label>
                    <input type="number" wire:model="quantite" class="mt-1 w-full input-field">
                    @error('quantite') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Montant</label>
                    <input type="number" step="0.01" wire:model="montant" class="mt-1 w-full input-field">
                    @error('montant') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

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
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-left">Désignation</th>
                    <th class="px-4 py-3 text-right">Qte</th>
                    <th class="px-4 py-3 text-right">Montant</th>
                    <th class="px-4 py-3 text-left">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($commandes as $commande)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $commande->numero }}</td>
                        <td class="px-4 py-3">{{ $commande->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $commande->client->nom ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $commande->designation }}</td>
                        <td class="px-4 py-3 text-right">{{ $commande->quantite }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($commande->montant, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if (auth()->user()->peutModifier('COMMANDES'))
                                <select wire:change="changerStatut({{ $commande->id }}, $event.target.value)" class="input-field text-xs py-1">
                                    @foreach ($statuts as $statut)
                                        <option value="{{ $statut }}" @selected($commande->statut === $statut)>{{ str_replace('_', ' ', $statut) }}</option>
                                    @endforeach
                                </select>
                            @else
                                {{ str_replace('_', ' ', $commande->statut) }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if (auth()->user()->peutModifier('COMMANDES'))
                                <x-action-delete call="supprimer({{ $commande->id }})" label="la commande {{ $commande->numero }}" />
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="8" message="Aucune commande." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $commandes->links() }}</div>
    </div>
</div>
