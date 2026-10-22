<div class="space-y-6">
    <div class="card p-5">
        <p class="text-xs uppercase tracking-wide text-brand-600 font-semibold mb-1">{{ auth()->user()->profil }}</p>
        <p class="text-sm text-slate-600">{{ $descriptionProfil }}</p>
    </div>

    <div class="card p-5">
        <h2 class="font-semibold text-slate-800 mb-1">Ce que vous pouvez faire</h2>
        <p class="text-xs text-slate-500 mb-4">
            <span class="badge-success">Lecture et modification</span> : vous pouvez créer, modifier et supprimer.
            <span class="badge-warning">Lecture seule</span> : vous pouvez seulement consulter.
        </p>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($modulesAccessibles as $module)
                <li class="py-3 flex items-start justify-between gap-4">
                    <div>
                        <p class="font-medium text-slate-800">{{ $module['label'] }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $module['description'] }}</p>
                    </div>
                    <span class="flex-shrink-0 {{ $module['modifiable'] ? 'badge-success' : 'badge-warning' }}">
                        {{ $module['modifiable'] ? 'Lecture et modification' : 'Lecture seule' }}
                    </span>
                </li>
            @empty
                <li class="py-3 text-slate-500">Aucun module accessible pour le moment.</li>
            @endforelse
        </ul>
    </div>

    @if ($modulesInaccessibles->isNotEmpty())
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-1">Ce que vous ne pouvez pas faire</h2>
            <p class="text-xs text-slate-500 mb-4">Ces modules ne font pas partie de votre profil. Contactez l'administrateur si vous pensez avoir besoin d'y accéder.</p>
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($modulesInaccessibles as $module)
                    <li class="py-2.5 flex items-center justify-between gap-4 text-slate-400">
                        <span>{{ $module['label'] }}</span>
                        <span class="badge-neutral">Pas d'accès</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
