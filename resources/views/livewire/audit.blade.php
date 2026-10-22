<div class="space-y-6">
    <p class="text-xs italic text-slate-500">Journal automatique de toutes les modifications et suppressions effectuées, avec le détail avant (et après) chaque action.</p>

    <div class="flex flex-col gap-3 sm:flex-row">
        <select wire:model.live="action" class="w-full sm:w-52 input-field">
            <option value="">Toutes les actions</option>
            <option value="modification">Modification</option>
            <option value="suppression">Suppression</option>
        </select>
        <select wire:model.live="module" class="w-full sm:w-64 input-field">
            <option value="">Toutes les feuilles</option>
            @foreach ($modules as $m)
                <option value="{{ $m }}">{{ $m }}</option>
            @endforeach
        </select>
    </div>

    <div class="card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-3 py-3 text-left">Date / Heure</th>
                    <th class="px-3 py-3 text-left">Utilisateur</th>
                    <th class="px-3 py-3 text-left">Profil</th>
                    <th class="px-3 py-3 text-left">Action</th>
                    <th class="px-3 py-3 text-left">Élément concerné</th>
                    <th class="px-3 py-3 text-left">Détail avant</th>
                    <th class="px-3 py-3 text-left">Détail après</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 align-top">
                @forelse ($lignes as $ligne)
                    <tr wire:key="audit-{{ $ligne->id }}">
                        <td class="px-3 py-2.5 whitespace-nowrap">{{ $ligne->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-3 py-2.5">{{ $ligne->utilisateur_nom ?? '—' }}</td>
                        <td class="px-3 py-2.5">{{ $ligne->utilisateur_profil ?? '—' }}</td>
                        <td class="px-3 py-2.5">
                            <span class="{{ $ligne->action === 'suppression' ? 'badge-danger' : 'badge-warning' }}">{{ ucfirst($ligne->action) }}</span>
                        </td>
                        <td class="px-3 py-2.5">{{ $ligne->module }}<br><span class="text-xs text-slate-500">{{ $ligne->libelle }}</span></td>
                        <td class="px-3 py-2.5 max-w-md break-words text-xs text-slate-600">{{ \App\Livewire\Audit::detail($ligne->donnees_avant) }}</td>
                        <td class="px-3 py-2.5 max-w-md break-words text-xs text-slate-600">{{ \App\Livewire\Audit::detail($ligne->donnees_apres) }}</td>
                    </tr>
                @empty
                    <x-etat-vide colspan="7" message="Aucune modification ni suppression enregistrée." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $lignes->links() }}</div>
    </div>
</div>
