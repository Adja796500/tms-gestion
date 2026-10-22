<div class="space-y-6">
    <div class="card p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="label-field">Action</label>
                <select wire:model.live="action" class="input-field">
                    <option value="">Toutes</option>
                    @foreach ($actions as $a)
                        <option value="{{ $a }}">{{ ucfirst($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-field">Module</label>
                <select wire:model.live="module" class="input-field">
                    <option value="">Tous</option>
                    @foreach ($modules as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-field">Utilisateur</label>
                <input type="text" wire:model.live.debounce.400ms="utilisateur" placeholder="nom d'utilisateur" class="input-field">
            </div>
            <div>
                <label class="label-field">Du</label>
                <input type="date" wire:model.live="dateDebut" class="input-field">
            </div>
            <div>
                <label class="label-field">Au</label>
                <input type="date" wire:model.live="dateFin" class="input-field">
            </div>
        </div>
        <div class="mt-3">
            <button type="button" wire:click="reinitialiser" class="btn-ghost">Réinitialiser les filtres</button>
        </div>
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">Date / heure</th>
                    <th class="px-4 py-3 text-left">Utilisateur</th>
                    <th class="px-4 py-3 text-left">Profil</th>
                    <th class="px-4 py-3 text-left">Action</th>
                    <th class="px-4 py-3 text-left">Module</th>
                    <th class="px-4 py-3 text-left">Concerne</th>
                    <th class="px-4 py-3 text-left">IP</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            @forelse ($logs as $log)
                <tbody class="divide-y divide-slate-100" x-data="{ ouvert: false }">
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3">{{ $log->utilisateur_nom ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $log->utilisateur_profil ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="badge-{{ match($log->action) {
                                'creation', 'connexion' => 'success',
                                'modification' => 'warning',
                                'suppression' => 'danger',
                                default => 'neutral',
                            } }}">{{ ucfirst($log->action) }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $log->module }}</td>
                        <td class="px-4 py-3">{{ $log->libelle ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $log->ip ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($log->donnees_avant || $log->donnees_apres)
                                <button type="button" @click="ouvert = !ouvert" class="link-edit" x-text="ouvert ? 'Masquer' : 'Detail'"></button>
                            @endif
                        </td>
                    </tr>
                    @if ($log->donnees_avant || $log->donnees_apres)
                        <tr x-show="ouvert" x-cloak>
                            <td colspan="8" class="px-4 py-3 bg-slate-50">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                    @if ($log->donnees_avant)
                                        <div>
                                            <p class="font-semibold text-slate-600 mb-1">Avant</p>
                                            <pre class="whitespace-pre-wrap break-all text-slate-500">{{ json_encode($log->donnees_avant, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                    @if ($log->donnees_apres)
                                        <div>
                                            <p class="font-semibold text-slate-600 mb-1">Après</p>
                                            <pre class="whitespace-pre-wrap break-all text-slate-500">{{ json_encode($log->donnees_apres, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            @empty
                <tbody>
                    <x-etat-vide colspan="8" message="Aucune activité enregistrée." />
                </tbody>
            @endforelse
        </table>
        <div class="p-4">{{ $logs->links() }}</div>
    </div>
</div>
