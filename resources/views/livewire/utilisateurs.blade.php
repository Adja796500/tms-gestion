<div class="space-y-6">
    <div class="flex justify-end">
        @if (auth()->user()->peutModifier('UTILISATEURS'))
            <button wire:click="creer" class="btn-primary">
                + Nouvel utilisateur
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">{{ $editingId ? 'Modifier' : 'Nouvel' }} utilisateur</h2>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label-field">Nom complet</label>
                    <input type="text" wire:model="name" class="mt-1 w-full input-field">
                    @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Nom d'utilisateur</label>
                    <input type="text" wire:model="username" class="mt-1 w-full input-field">
                    @error('username') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Email (optionnel)</label>
                    <input type="email" wire:model="email" class="mt-1 w-full input-field">
                </div>
                <div>
                    <label class="label-field">Profil</label>
                    <select wire:model="profil" class="mt-1 w-full input-field">
                        @foreach ($profils as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-field">Mot de passe {{ $editingId ? '(laisser vide pour ne pas changer)' : '' }}</label>
                    <input type="password" wire:model="password" class="mt-1 w-full input-field">
                    @error('password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-2 mt-6">
                    <input type="checkbox" wire:model="actif" id="actif" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/40">
                    <label for="actif" class="text-sm text-slate-600">Compte actif</label>
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
                    <th class="px-4 py-3 text-left">Utilisateur</th>
                    <th class="px-4 py-3 text-left">Nom</th>
                    <th class="px-4 py-3 text-left">Profil</th>
                    <th class="px-4 py-3 text-left">Statut</th>
                    <th class="px-4 py-3 text-left">Dernière connexion</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($utilisateurs as $u)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $u->username }}</td>
                        <td class="px-4 py-3">{{ $u->name }}</td>
                        <td class="px-4 py-3">{{ $u->profil }}</td>
                        <td class="px-4 py-3">
                            @if ($u->actif)
                                <span class="badge-success">Actif</span>
                            @else
                                <span class="badge-neutral">Inactif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $u->derniere_connexion?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if (auth()->user()->peutModifier('UTILISATEURS'))
                                <x-action-edit call="modifier({{ $u->id }})" />
                                <x-action-delete call="supprimer({{ $u->id }})" label="l'utilisateur {{ $u->username }}" />
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="6" message="Aucun utilisateur." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $utilisateurs->links() }}</div>
    </div>
</div>
