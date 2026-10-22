<div class="space-y-6">
    <div class="flex justify-end">
        @if (auth()->user()->peutModifier('REGLEMENTS_FOURNISSEURS'))
            <button wire:click="creer" class="btn-primary">
                + Nouveau règlement
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">Nouveau règlement fournisseur</h2>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label-field">Date</label>
                    <input type="date" wire:model="date" class="mt-1 w-full input-field">
                </div>
                <div>
                    <label class="label-field">Fournisseur</label>
                    <select wire:model="fournisseur_id" class="mt-1 w-full input-field">
                        <option value="">-- Choisir --</option>
                        @foreach ($fournisseursDisponibles as $fournisseur)
                            <option value="{{ $fournisseur->id }}">{{ $fournisseur->nom }}</option>
                        @endforeach
                    </select>
                    @error('fournisseur_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Achat lie (optionnel)</label>
                    <select wire:model="achat_id" class="mt-1 w-full input-field">
                        <option value="">-- Aucun --</option>
                        @foreach ($achatsDisponibles as $achat)
                            <option value="{{ $achat->id }}">{{ $achat->numero }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-field">Montant regle</label>
                    <input type="number" step="0.01" wire:model="montant" class="mt-1 w-full input-field">
                    @error('montant') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Mode de paiement</label>
                    <select wire:model="mode_paiement" class="mt-1 w-full input-field">
                        <option value="Especes">Especes</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Virement">Virement</option>
                        <option value="Mobile Money">Mobile Money</option>
                    </select>
                </div>
                <div>
                    <label class="label-field">Observations</label>
                    <input type="text" wire:model="observations" class="mt-1 w-full input-field">
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
                    <th class="px-4 py-3 text-left">Fournisseur</th>
                    <th class="px-4 py-3 text-left">Mode</th>
                    <th class="px-4 py-3 text-right">Montant</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($reglements as $reglement)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $reglement->numero }}</td>
                        <td class="px-4 py-3">{{ $reglement->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $reglement->fournisseur->nom ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $reglement->mode_paiement }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($reglement->montant, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if (auth()->user()->peutModifier('REGLEMENTS_FOURNISSEURS'))
                                <x-action-delete call="supprimer({{ $reglement->id }})" label="le règlement {{ $reglement->numero }}" />
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="6" message="Aucun règlement." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $reglements->links() }}</div>
    </div>
</div>
