<div class="space-y-6">
    <div class="flex justify-end">
        @if (auth()->user()->peutModifier('PAIEMENTS_CLIENTS'))
            <button wire:click="creer" class="btn-primary">
                + Nouveau paiement
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">Nouveau paiement client</h2>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label-field">Date</label>
                    <input type="date" wire:model="date" class="mt-1 w-full input-field">
                </div>
                <div>
                    <label class="label-field">Client</label>
                    <select wire:model.live="client_id" class="mt-1 w-full input-field">
                        <option value="">-- Choisir --</option>
                        @foreach ($clientsDisponibles as $client)
                            <option value="{{ $client->id }}">{{ $client->nom }}</option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Vente liee (optionnel)</label>
                    <select wire:model="vente_id" class="mt-1 w-full input-field">
                        <option value="">-- Aucune --</option>
                        @foreach ($ventesDisponibles as $vente)
                            <option value="{{ $vente->id }}">{{ $vente->numero }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-field">Facture réglée (optionnel)</label>
                    <select wire:model="facture_id" class="mt-1 w-full input-field">
                        <option value="">-- Aucune --</option>
                        @foreach ($facturesDisponibles as $facture)
                            <option value="{{ $facture->id }}">{{ $facture->numero }} — {{ number_format($facture->montant_total, 0, ',', ' ') }}</option>
                        @endforeach
                    </select>
                    @error('facture_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Montant payé</label>
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
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-left">Mode</th>
                    <th class="px-4 py-3 text-right">Montant</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($paiements as $paiement)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $paiement->numero }}</td>
                        <td class="px-4 py-3">{{ $paiement->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $paiement->client->nom ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $paiement->mode_paiement }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($paiement->montant, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if (auth()->user()->peutModifier('PAIEMENTS_CLIENTS'))
                                <x-action-delete call="supprimer({{ $paiement->id }})" label="le paiement {{ $paiement->numero }}" />
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="6" message="Aucun paiement." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $paiements->links() }}</div>
    </div>
</div>
