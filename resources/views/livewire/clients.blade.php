<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Rechercher un client..."
               class="w-full sm:w-80 input-field">
        @if (auth()->user()->peutModifier('CLIENTS'))
            <button wire:click="creer" class="btn-primary">
                + Nouveau client
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">{{ $editingId ? 'Modifier' : 'Nouveau' }} client</h2>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="label-field">Nom / Raison sociale</label>
                    <input type="text" wire:model="nom" class="mt-1 w-full input-field">
                    @error('nom') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Téléphone</label>
                    <input type="text" wire:model="telephone" class="mt-1 w-full input-field">
                </div>
                <div>
                    <label class="label-field">Email</label>
                    <input type="email" wire:model="email" class="mt-1 w-full input-field">
                    @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label-field">Adresse</label>
                    <input type="text" wire:model="adresse" class="mt-1 w-full input-field">
                </div>
                <div class="sm:col-span-2 flex gap-3 pt-2">
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
                    <th class="px-4 py-3 text-left">Nom</th>
                    <th class="px-4 py-3 text-left">Téléphone</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-right">Ventes</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($clients as $client)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $client->nom }}</td>
                        <td class="px-4 py-3">{{ $client->telephone }}</td>
                        <td class="px-4 py-3">{{ $client->email }}</td>
                        <td class="px-4 py-3 text-right">{{ $client->ventes_count }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if (auth()->user()->peutModifier('CLIENTS'))
                                <x-action-edit call="modifier({{ $client->id }})" />
                                <x-action-delete call="supprimer({{ $client->id }})" label="le client {{ $client->nom }}" />
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="5" message="Aucun client." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $clients->links() }}</div>
    </div>
</div>
