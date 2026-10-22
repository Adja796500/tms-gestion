<div class="space-y-6">
    <div class="flex justify-end">
        @if (auth()->user()->peutModifier('RECU'))
            <button wire:click="creer" class="btn-primary">
                + Nouveau reçu
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">Générer un reçu à partir d'un paiement client</h2>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="label-field">Paiement client</label>
                    <select wire:model="paiement_client_id" class="mt-1 w-full input-field">
                        <option value="">-- Choisir un paiement sans reçu --</option>
                        @foreach ($paiementsSansRecu as $paiement)
                            <option value="{{ $paiement->id }}">{{ $paiement->numero }} — {{ $paiement->client->nom ?? '' }} ({{ number_format($paiement->montant, 0, ',', ' ') }})</option>
                        @endforeach
                    </select>
                    @error('paiement_client_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3 flex gap-3 pt-2">
                    <x-btn-submit>Générer</x-btn-submit>
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
                    <th class="px-4 py-3 text-right">Montant</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($recus as $recu)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $recu->numero }}</td>
                        <td class="px-4 py-3">{{ $recu->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $recu->paiementClient->client->nom ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($recu->montant, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('recus.imprimer', $recu) }}" target="_blank" class="link-edit mr-3">Imprimer</a>
                            @if (auth()->user()->peutModifier('RECU'))
                                <x-action-delete call="supprimer({{ $recu->id }})" label="le reçu {{ $recu->numero }}" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="5" message="Aucun reçu." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $recus->links() }}</div>
    </div>
</div>
