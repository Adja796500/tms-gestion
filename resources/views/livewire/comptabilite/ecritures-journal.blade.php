<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="font-semibold text-slate-800">Journal des transactions</h2>
            <p class="text-xs italic text-slate-500">Saisissez ici chaque entrée/dépense quotidienne. Les ventes et les services s'y ajoutent automatiquement.</p>
        </div>
        @if (auth()->user()->peutModifier('COMPTABILITE_JOURNAL'))
            <button wire:click="creer" class="btn-primary">
                + Nouvelle écriture
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">{{ $editingId ? "Modifier l'ecriture" : 'Nouvelle ecriture' }}</h2>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label-field">Date</label>
                    <input type="date" wire:model="date" class="mt-1 w-full input-field">
                    @error('date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Type</label>
                    <select wire:model="type" class="mt-1 w-full input-field">
                        <option value="Entree">Entrée</option>
                        <option value="Depense">Dépense</option>
                    </select>
                </div>
                <div>
                    <label class="label-field">Catégorie d'activité</label>
                    <select wire:model="categorie_activite_id" class="mt-1 w-full input-field">
                        <option value="">-- Aucune --</option>
                        @foreach ($categoriesDisponibles as $categorie)
                            <option value="{{ $categorie->id }}">{{ $categorie->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="label-field">Description</label>
                    <input type="text" wire:model="description" class="mt-1 w-full input-field">
                    @error('description') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Montant</label>
                    <input type="number" step="0.01" wire:model="montant" class="mt-1 w-full input-field">
                    @error('montant') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Moyen de paiement</label>
                    <select wire:model="moyen_paiement" class="mt-1 w-full input-field">
                        <option value="Especes">Especes</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Virement">Virement</option>
                        <option value="Mobile Money">Mobile Money</option>
                    </select>
                    <p class="text-xs text-slate-400 mt-1">Le compte (Caisse/Banque) est deduit automatiquement.</p>
                </div>

                <div class="sm:col-span-3 flex gap-3 pt-2">
                    <x-btn-submit>Enregistrer</x-btn-submit>
                    <button type="button" wire:click="annuler" class="btn-ghost">Annuler</button>
                </div>
            </form>
        </div>
    @endif

    <div class="card p-4 print:hidden">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7">
            <div class="col-span-2 xl:col-span-2">
                <label class="label-field">Recherche</label>
                <input type="text" wire:model.live.debounce.400ms="recherche" placeholder="Description..." class="mt-1 input-field">
            </div>
            <div>
                <label class="label-field">Du</label>
                <input type="date" wire:model.live="du" class="mt-1 input-field">
            </div>
            <div>
                <label class="label-field">Au</label>
                <input type="date" wire:model.live="au" class="mt-1 input-field">
            </div>
            <div>
                <label class="label-field">Catégorie</label>
                <select wire:model.live="filtreCategorie" class="mt-1 input-field">
                    <option value="">Toutes</option>
                    @foreach ($categoriesDisponibles as $categorie)
                        <option value="{{ $categorie->id }}">{{ $categorie->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-field">Type</label>
                <select wire:model.live="filtreType" class="mt-1 input-field">
                    <option value="">Tous</option>
                    <option value="Entree">Entrée</option>
                    <option value="Depense">Dépense</option>
                </select>
            </div>
            <div>
                <label class="label-field">Compte</label>
                <select wire:model.live="filtreCompte" class="mt-1 input-field">
                    <option value="">Tous</option>
                    <option value="Caisse">Caisse</option>
                    <option value="Banque">Banque</option>
                </select>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
            <button type="button" wire:click="filtrerMoisEnCours" class="btn-ghost">Ce mois-ci</button>
            @if ($filtresActifs)
                <button type="button" wire:click="reinitialiserFiltres" class="btn-ghost">Effacer les filtres</button>
            @endif
            <div class="ml-auto flex gap-2">
                <button type="button" onclick="window.print()" class="btn-ghost">Imprimer</button>
                <a href="{{ $lienExport }}" class="btn-primary">Exporter (Excel)</a>
            </div>
        </div>
    </div>

    <div class="cascade grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-kpi-card icon="trending-up" color="emerald" label="Entrées{{ $filtresActifs ? ' (filtre)' : '' }}" value="{{ number_format($totalEntrees, 0, ',', ' ') }} {{ config('app.currency') }}" />
        <x-kpi-card icon="cart" color="rose" label="Dépenses{{ $filtresActifs ? ' (filtre)' : '' }}" value="{{ number_format($totalDepenses, 0, ',', ' ') }} {{ config('app.currency') }}" />
        <x-kpi-card icon="banknotes" :color="$totalEntrees - $totalDepenses >= 0 ? 'brand' : 'rose'" label="Solde{{ $filtresActifs ? ' (filtre)' : '' }}" value="{{ number_format($totalEntrees - $totalDepenses, 0, ',', ' ') }} {{ config('app.currency') }}" />
    </div>

    <div class="card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-3 py-3 text-right">N°</th>
                    <th class="px-3 py-3 text-left">Date</th>
                    <th class="px-3 py-3 text-left">Mois (auto)</th>
                    <th class="px-3 py-3 text-left">Catégorie</th>
                    <th class="px-3 py-3 text-left">Type</th>
                    <th class="px-3 py-3 text-left">Description</th>
                    <th class="px-3 py-3 text-right">Montant ({{ config('app.currency') }})</th>
                    <th class="px-3 py-3 text-left">Mode de paiement</th>
                    <th class="px-3 py-3 text-left">Compte (auto)</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ecritures as $ecriture)
                    <tr wire:key="ecriture-{{ $ecriture->id }}">
                        <td class="px-3 py-2.5 text-right text-slate-500">{{ $ecritures->total() - ($ecritures->firstItem() + $loop->index) + 1 }}</td>
                        <td class="px-3 py-2.5 whitespace-nowrap">{{ $ecriture->date->format('d/m/Y') }}</td>
                        <td class="px-3 py-2.5 whitespace-nowrap">{{ $ecriture->mois() }}</td>
                        <td class="px-3 py-2.5">{{ $ecriture->categorieActivite->nom ?? '—' }}</td>
                        <td class="px-3 py-2.5">
                            <span class="{{ $ecriture->type === 'Entree' ? 'badge-success' : 'badge-danger' }}">{{ $ecriture->libelleType() }}</span>
                        </td>
                        <td class="px-3 py-2.5">{{ $ecriture->description }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ number_format($ecriture->montant, 0, ',', ' ') }} {{ config('app.currency') }}</td>
                        <td class="px-3 py-2.5">{{ $ecriture->libelleMoyenPaiement() }}</td>
                        <td class="px-3 py-2.5 text-emerald-700">{{ $ecriture->compte }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            @if ($ecriture->vente_id)
                                <span class="text-xs text-slate-400" title="Ligne générée par une vente : modifiez ou supprimez la vente">Auto (vente)</span>
                            @elseif (auth()->user()->peutModifier('COMPTABILITE_JOURNAL'))
                                <x-action-edit call="modifier({{ $ecriture->id }})" />
                                <x-action-delete call="supprimer({{ $ecriture->id }})" label="cette écriture" />
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="10" message="Aucune écriture." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $ecritures->links() }}</div>
    </div>
</div>
