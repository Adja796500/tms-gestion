<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <label class="label-field whitespace-nowrap">Afficher :</label>
            <select wire:model.live="filtreService" class="w-full sm:w-64 input-field">
                <option value="">Tous les services</option>
                @foreach ($services as $nom)
                    <option value="{{ $nom }}">{{ $nom }}</option>
                @endforeach
            </select>
        </div>
        @if (auth()->user()->peutModifier('SERVICES'))
            <button wire:click="creer" class="btn-success">
                + Ajouter
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-1">{{ $editingId ? 'Modifier une écriture' : 'Nouvelle entrée / dépense de service' }}</h2>
            <p class="text-xs text-slate-500 mb-4">
                @if ($editingId)
                    En modification, remplissez un seul des deux montants.
                @else
                    Remplissez l'Entrée, la Dépense, ou les deux : deux écritures distinctes seront alors créées dans le Journal.
                @endif
            </p>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="label-field">Désignation</label>
                    <input type="text" wire:model="designation" class="mt-1 w-full input-field" placeholder="Ex. Affiche Adjara">
                    @error('designation') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Service</label>
                    <select wire:model="service" class="mt-1 w-full input-field">
                        <option value="">-- Choisir --</option>
                        @foreach ($services as $nom)
                            <option value="{{ $nom }}">{{ $nom }}</option>
                        @endforeach
                    </select>
                    @error('service') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Montant Entrée</label>
                    <input type="number" step="1" min="0" wire:model="montantEntree" class="mt-1 w-full input-field">
                    @error('montantEntree') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Montant Dépense</label>
                    <input type="number" step="1" min="0" wire:model="montantDepense" class="mt-1 w-full input-field">
                    @error('montantDepense') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Bénéfice</label>
                    {{-- Recalcule a chaque frappe (Entree - Depense), sans aller-retour serveur. --}}
                    <div x-data="{ get benefice() { return (Number($wire.montantEntree) || 0) - (Number($wire.montantDepense) || 0) } }"
                         x-text="benefice.toLocaleString('fr-FR') + ' {{ config('app.currency') }}'"
                         :class="benefice < 0 ? 'text-rose-600' : 'text-emerald-700'"
                         class="mt-1 w-full input-field bg-slate-50 font-semibold"></div>
                </div>
                <div>
                    <label class="label-field">Date</label>
                    <input type="date" wire:model="date" class="mt-1 w-full input-field">
                    @error('date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Mode de paiement</label>
                    <select wire:model="moyen_paiement" class="mt-1 w-full input-field">
                        @foreach (\App\Livewire\Services::MOYENS_PAIEMENT as $moyen)
                            <option value="{{ $moyen }}">{{ \App\Models\EcritureJournal::MOYENS_PAIEMENT[$moyen] ?? $moyen }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-3 flex gap-3 pt-2">
                    <x-btn-submit>Valider</x-btn-submit>
                    <button type="button" wire:click="annuler" class="btn-ghost">Fermer</button>
                </div>
            </form>
        </div>
    @endif

    <div class="cascade grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-kpi-card icon="trending-up" color="emerald" label="Total entrées"
                    value="{{ number_format($totalEntrees, 0, ',', ' ') }} {{ config('app.currency') }}" />
        <x-kpi-card icon="cart" color="rose" label="Total dépenses"
                    value="{{ number_format($totalDepenses, 0, ',', ' ') }} {{ config('app.currency') }}" />
        <x-kpi-card icon="banknotes" :color="$totalEntrees - $totalDepenses >= 0 ? 'brand' : 'rose'" label="Bénéfice"
                    value="{{ number_format($totalEntrees - $totalDepenses, 0, ',', ' ') }} {{ config('app.currency') }}" />
    </div>

    <div class="card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Service</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Description</th>
                    <th class="px-4 py-3 text-right">Montant</th>
                    <th class="px-4 py-3 text-left">Mode de paiement</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ecritures as $ecriture)
                    <tr wire:key="service-{{ $ecriture->id }}">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $ecriture->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $ecriture->categorieActivite->nom ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $ecriture->type === 'Entree' ? 'badge-success' : 'badge-danger' }}">{{ $ecriture->libelleType() }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $ecriture->description }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">{{ number_format($ecriture->montant, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3">{{ $ecriture->libelleMoyenPaiement() }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if (auth()->user()->peutModifier('SERVICES'))
                                <x-action-edit call="modifier({{ $ecriture->id }})" />
                                <x-action-delete call="supprimer({{ $ecriture->id }})" label="cette écriture" />
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="7" message="Aucune écriture de service." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $ecritures->links() }}</div>
    </div>
</div>
