<div class="space-y-6">
    <div class="card p-5">
        <h2 class="font-semibold text-slate-800 mb-2">Entreprise</h2>
        <p class="text-sm text-slate-600">{{ config('app.name') }}</p>
        <p class="text-xs text-slate-400 mt-1">Modifiable via la variable APP_NAME du fichier .env.</p>
    </div>

    <div class="card p-5">
        <h2 class="font-semibold text-slate-800 mb-1">Matrice des droits d'accès</h2>
        <p class="text-xs text-slate-500 mb-4">
            Reprise directement de la feuille DROITS_ACCES du classeur d'origine. L'administrateur a toujours accès complet.
            @if (auth()->user()->isAdministrateur())
                <strong>Accès</strong> = peut consulter le module. <strong>Modif.</strong> = peut aussi créer/modifier/supprimer (inactif si pas d'accès).
                Chaque changement est tracé dans le <a href="{{ route('journal.index') }}" class="link-edit">Journal d'activité</a>.
            @else
                ✓ seul = lecture seule. ✓✎ = lecture et modification.
            @endif
        </p>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="table-head">
                    <tr>
                        <th class="px-3 py-2 text-left">Module</th>
                        @foreach ($roles as $role)
                            <th class="px-3 py-2 text-center">{{ $role }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($modules as $module => $regles)
                        <tr>
                            <td class="px-3 py-2 font-medium">{{ $module }}</td>
                            @foreach ($roles as $role)
                                @php($regle = $regles->firstWhere('role', $role))
                                <td class="px-3 py-2 text-center">
                                    @if ($role === 'Administrateur')
                                        <span class="text-emerald-600" title="Accès complet">✓✎</span>
                                    @elseif (auth()->user()->isAdministrateur() && $regle)
                                        <div class="flex flex-col items-center gap-1">
                                            <label class="flex items-center gap-1 cursor-pointer" title="Accès au module">
                                                <input type="checkbox" wire:click="basculer({{ $regle->id }})" @checked($regle->autorise)
                                                       class="h-3.5 w-3.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500/40 cursor-pointer">
                                                <span class="text-[10px] text-slate-500">Accès</span>
                                            </label>
                                            <label class="flex items-center gap-1 {{ $regle->autorise ? 'cursor-pointer' : 'opacity-30 cursor-not-allowed' }}" title="Peut créer/modifier/supprimer">
                                                <input type="checkbox" wire:click="basculerModification({{ $regle->id }})" @checked($regle->peut_modifier)
                                                       @disabled(! $regle->autorise)
                                                       class="h-3.5 w-3.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500/40 cursor-pointer">
                                                <span class="text-[10px] text-slate-500">Modif.</span>
                                            </label>
                                        </div>
                                    @elseif ($regle?->autorise && $regle?->peut_modifier)
                                        <span class="text-emerald-600" title="Lecture et modification">✓✎</span>
                                    @elseif ($regle?->autorise)
                                        <span class="text-amber-600" title="Lecture seule">✓</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
