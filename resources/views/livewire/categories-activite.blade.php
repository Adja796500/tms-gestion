<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Rechercher une catégorie..."
               class="w-full sm:w-80 input-field">
        @if (auth()->user()->peutModifier('CATEGORIES_ACTIVITE'))
            <button wire:click="creer" class="btn-primary">
                + Nouvelle catégorie
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">{{ $editingId ? 'Modifier' : 'Nouvelle' }} catégorie</h2>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="label-field">Nom</label>
                    <input type="text" wire:model="nom" class="mt-1 w-full input-field">
                    @error('nom') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="actif" id="actif" class="rounded border-slate-300">
                    <label for="actif" class="label-field">Catégorie active</label>
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
                    <th class="px-4 py-3 text-left">Statut</th>
                    <th class="px-4 py-3 text-right">Articles</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($categories as $categorie)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $categorie->nom }}</td>
                        <td class="px-4 py-3 text-xs">{{ $categorie->actif ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-3 text-right">{{ $categorie->articles_count }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if (auth()->user()->peutModifier('CATEGORIES_ACTIVITE'))
                                <x-action-edit call="modifier({{ $categorie->id }})" />
                                <x-action-delete call="supprimer({{ $categorie->id }})" label="la catégorie {{ $categorie->nom }}" />
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="4" message="Aucune catégorie." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $categories->links() }}</div>
    </div>
</div>
