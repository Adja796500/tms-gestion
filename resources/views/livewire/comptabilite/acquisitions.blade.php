<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h2 class="font-semibold text-slate-800">Acquisitions & prêts</h2>
        @if (auth()->user()->peutModifier('COMPTABILITE_ACQUISITIONS'))
            <button wire:click="creer" class="btn-primary">
                + Nouvelle acquisition
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">Nouvelle acquisition</h2>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label-field">Date</label>
                    <input type="date" wire:model="date" class="mt-1 w-full input-field">
                    @error('date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Type</label>
                    <select wire:model="type" class="mt-1 w-full input-field">
                        <option value="Acquisition">Acquisition</option>
                        <option value="Pret_Benefices">Pret sur bénéfices</option>
                        <option value="Depense_Reserve">Dépense sur réserve</option>
                    </select>
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
                <div class="sm:col-span-2">
                    <label class="label-field">Désignation</label>
                    <input type="text" wire:model="designation" class="mt-1 w-full input-field">
                    @error('designation') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
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

    <div class="card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Désignation</th>
                    <th class="px-4 py-3 text-left">Fournisseur</th>
                    <th class="px-4 py-3 text-right">Montant</th>
                    <th class="px-4 py-3 text-left">Compte</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($acquisitions as $acquisition)
                    <tr>
                        <td class="px-4 py-3">{{ $acquisition->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-xs">{{ $acquisition->type }}</td>
                        <td class="px-4 py-3">{{ $acquisition->designation }}</td>
                        <td class="px-4 py-3">{{ $acquisition->fournisseur->nom ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($acquisition->montant, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3">{{ $acquisition->compte }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if (auth()->user()->peutModifier('COMPTABILITE_ACQUISITIONS'))
                                <x-action-delete call="supprimer({{ $acquisition->id }})" label="cette acquisition" />
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="7" message="Aucune acquisition." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $acquisitions->links() }}</div>
    </div>
</div>
