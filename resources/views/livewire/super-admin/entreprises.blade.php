<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">Chaque entreprise a ses propres données, utilisateurs, logo et documents, totalement séparés des autres.</p>
        <button wire:click="creer" class="btn-primary">
            + Nouvelle entreprise
        </button>
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">Nouvelle entreprise</h2>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="label-field">Nom de l'entreprise</label>
                    <input type="text" wire:model="nomEntreprise" class="mt-1 w-full input-field">
                    @error('nomEntreprise') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs uppercase tracking-wide text-slate-400 font-semibold mt-2">Premier compte administrateur</p>
                    <p class="text-xs text-slate-500">C'est avec ce compte que le client se connecte la première fois. Il pourra ensuite créer ses autres utilisateurs (caissier, comptable...) et déposer son logo dans « Configuration entreprise ».</p>
                </div>
                <div>
                    <label class="label-field">Nom complet</label>
                    <input type="text" wire:model="adminName" class="mt-1 w-full input-field">
                    @error('adminName') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Nom d'utilisateur</label>
                    <input type="text" wire:model="adminUsername" class="mt-1 w-full input-field">
                    @error('adminUsername') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label-field">Mot de passe (8 caractères minimum)</label>
                    <input type="text" wire:model="adminPassword" autocomplete="off" class="mt-1 w-full input-field font-mono">
                    <p class="text-xs text-slate-400 mt-1">Affiché en clair pour pouvoir le noter et le transmettre au client.</p>
                    @error('adminPassword') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 flex gap-3 pt-2">
                    <x-btn-submit>Créer l'entreprise</x-btn-submit>
                    <button type="button" wire:click="annuler" class="btn-ghost">Annuler</button>
                </div>
            </form>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">Entreprise</th>
                    <th class="px-4 py-3 text-right">Utilisateurs</th>
                    <th class="px-4 py-3 text-left">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($entreprises as $entreprise)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $entreprise->nom }}</td>
                        <td class="px-4 py-3 text-right">{{ $entreprise->users_count }}</td>
                        <td class="px-4 py-3">
                            @if ($entreprise->actif)
                                <span class="badge-success">Active</span>
                            @else
                                <span class="badge-danger">Désactivée</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button wire:click="ouvrirRenommage({{ $entreprise->id }})" class="link-edit mr-3">Renommer</button>
                            <button wire:click="ouvrirMotDePasse({{ $entreprise->id }})" class="link-edit mr-3">Mot de passe</button>
                            <button wire:click="basculerActif({{ $entreprise->id }})"
                                    wire:confirm="{{ $entreprise->actif ? 'Desactiver' : 'Reactiver' }} cette entreprise ?"
                                    class="{{ $entreprise->actif ? 'link-danger' : 'link-edit' }}">
                                {{ $entreprise->actif ? 'Desactiver' : 'Reactiver' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="4" message="Aucune entreprise." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $entreprises->links() }}</div>
    </div>

    @if ($renommageId)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" @click.outside="$wire.fermer()">
                <h3 class="mb-4 font-semibold text-slate-900">Renommer l'entreprise</h3>
                <form wire:submit="renommer" class="space-y-4">
                    <input type="text" wire:model="nouveauNom" class="input-field" autofocus>
                    @error('nouveauNom') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    <p class="text-xs text-slate-500">Nom affiché dans le menu de l'entreprise. Le nom imprimé sur ses documents se règle dans sa « Configuration entreprise ».</p>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="fermer" class="btn-ghost">Annuler</button>
                        <x-btn-submit target="renommer">Enregistrer</x-btn-submit>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($entrepriseMotDePasse)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" @click.outside="$wire.fermer()">
                <h3 class="font-semibold text-slate-900">Réinitialiser un mot de passe</h3>
                <p class="mb-4 text-sm text-slate-500">{{ $entrepriseMotDePasse->nom }}</p>
                <form wire:submit="reinitialiserMotDePasse" class="space-y-4">
                    <div>
                        <label class="label-field">Utilisateur</label>
                        <select wire:model="utilisateurId" class="mt-1 input-field">
                            @foreach ($utilisateursEntreprise as $u)
                                <option value="{{ $u->id }}">{{ $u->username }} — {{ $u->name }} ({{ $u->profil }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label-field">Nouveau mot de passe (8 caractères minimum)</label>
                        <input type="text" wire:model="nouveauMotDePasse" autocomplete="off" class="mt-1 input-field font-mono">
                        @error('nouveauMotDePasse') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="fermer" class="btn-ghost">Annuler</button>
                        <x-btn-submit target="reinitialiserMotDePasse">Réinitialiser</x-btn-submit>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
