@php($fcfa = fn ($v) => number_format($v, 0, ',', ' ').' '.config('app.currency'))
<div class="space-y-6">
    <p class="text-xs italic text-slate-500">Analyses automatiques calculées à partir du Journal et des Acquisitions.</p>

    <div class="card overflow-x-auto">
        <h2 class="p-5 pb-3 font-semibold text-slate-800">Indicateurs clés</h2>
        <table class="min-w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                <tr><td class="px-4 py-2.5">Mois le plus rentable</td><td class="px-4 py-2.5 text-right font-semibold">{{ $meilleur['libelle'] ?? '—' }}</td></tr>
                <tr><td class="px-4 py-2.5">Meilleur bénéfice mensuel</td><td class="px-4 py-2.5 text-right font-semibold text-emerald-700">{{ $fcfa($meilleur['benefice'] ?? 0) }}</td></tr>
                <tr><td class="px-4 py-2.5">Mois le plus faible</td><td class="px-4 py-2.5 text-right font-semibold">{{ $plusFaible['libelle'] ?? '—' }}</td></tr>
                <tr><td class="px-4 py-2.5">Bénéfice mensuel le plus faible</td><td class="px-4 py-2.5 text-right font-semibold {{ ($plusFaible['benefice'] ?? 0) < 0 ? 'text-rose-600' : '' }}">{{ $fcfa($plusFaible['benefice'] ?? 0) }}</td></tr>
                <tr><td class="px-4 py-2.5">Bénéfice moyen / mois</td><td class="px-4 py-2.5 text-right font-semibold">{{ $fcfa($beneficeMoyen) }}</td></tr>
                <tr><td class="px-4 py-2.5">Marge moyenne (Bénéfice / Entrées)</td><td class="px-4 py-2.5 text-right font-semibold">{{ number_format($margeMoyenne * 100, 1, ',', ' ') }} %</td></tr>
                <tr><td class="px-4 py-2.5">Nombre de transactions saisies</td><td class="px-4 py-2.5 text-right font-semibold">{{ $nbTransactions }}</td></tr>
                <tr><td class="px-4 py-2.5">Nombre d'acquisitions enregistrées</td><td class="px-4 py-2.5 text-right font-semibold">{{ $nbAcquisitions }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="card p-5">
        <h2 class="mb-4 font-semibold text-slate-800">Bénéfice par mois</h2>
        @php($max = max(1, $mensuel->max(fn ($l) => abs($l['benefice']))))
        <div class="space-y-2">
            @foreach ($mensuel as $ligne)
                <div class="flex items-center gap-3 text-sm">
                    <span class="w-32 flex-shrink-0 text-slate-600">{{ $ligne['libelle'] }}</span>
                    <div class="h-4 flex-1 rounded bg-slate-100">
                        <div class="h-4 rounded {{ $ligne['benefice'] < 0 ? 'bg-rose-400' : 'bg-emerald-500' }}" style="width: {{ round(abs($ligne['benefice']) / $max * 100, 1) }}%"></div>
                    </div>
                    <span class="w-32 flex-shrink-0 text-right font-medium {{ $ligne['benefice'] < 0 ? 'text-rose-600' : 'text-slate-800' }}">{{ $fcfa($ligne['benefice']) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card overflow-x-auto">
        <h2 class="p-5 pb-3 font-semibold text-slate-800">Performance par catégorie (Entrées vs Dépenses)</h2>
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">Catégorie</th>
                    <th class="px-4 py-3 text-right">Entrées</th>
                    <th class="px-4 py-3 text-right">Dépenses</th>
                    <th class="px-4 py-3 text-right">Bénéfice</th>
                    <th class="px-4 py-3 text-right">Part du bénéfice total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($parCategorie as $ligne)
                    <tr>
                        <td class="px-4 py-2.5">{{ $ligne['categorie'] }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $fcfa($ligne['entrees']) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $fcfa($ligne['depenses']) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $fcfa($ligne['benefice']) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $beneficeTotal != 0 ? number_format($ligne['benefice'] / $beneficeTotal * 100, 1, ',', ' ').' %' : '—' }}</td>
                    </tr>
                @empty
                    <x-etat-vide colspan="5" message="Aucune écriture au Journal." />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card overflow-x-auto">
        <h2 class="p-5 pb-3 font-semibold text-slate-800">Top 10 des plus grosses acquisitions</h2>
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Désignation</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-right">Montant</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($topAcquisitions as $acquisition)
                    <tr>
                        <td class="px-4 py-2.5">{{ $loop->iteration }}</td>
                        <td class="px-4 py-2.5">{{ $acquisition->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5">{{ $acquisition->designation }}</td>
                        <td class="px-4 py-2.5">{{ \App\Support\Finances::TYPES_ACQUISITION[$acquisition->type] ?? $acquisition->type }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $fcfa($acquisition->montant) }}</td>
                    </tr>
                @empty
                    <x-etat-vide colspan="5" message="Aucune acquisition enregistrée." />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
