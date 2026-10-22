@php($fcfa = fn ($v) => number_format($v, 0, ',', ' ').' '.config('app.currency'))
<div class="space-y-6">
    <div class="cascade grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-kpi-card icon="trending-up" color="emerald" label="Chiffre d'affaires" value="{{ $fcfa($resultat['entrees']) }}" />
        <x-kpi-card icon="cart" color="rose" label="Dépenses" value="{{ $fcfa($resultat['depenses']) }}" />
        <x-kpi-card icon="banknotes" :color="$resultat['benefice'] >= 0 ? 'brand' : 'rose'" label="Bénéfice" value="{{ $fcfa($resultat['benefice']) }}" />
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="card p-4">
            <p class="text-xs font-medium uppercase text-slate-500">Bénéfice total</p>
            <p class="mt-1 text-lg font-bold text-slate-900">{{ $fcfa($resultat['benefice']) }}</p>
        </div>
        @foreach (\App\Support\Finances::POSTES as $cle => $poste)
            <div class="card p-4">
                <p class="text-xs font-medium uppercase text-slate-500">{{ $poste }} ({{ number_format($taux[$cle] * 100, 0) }} %)</p>
                <p class="mt-1 text-lg font-bold text-slate-900">{{ $fcfa($repartition[$cle]) }}</p>
            </div>
        @endforeach
    </div>

    <div class="card overflow-x-auto">
        <div class="p-5 pb-0">
            <h2 class="font-semibold text-slate-800">Synthèse mensuelle (mise à jour automatique)</h2>
        </div>
        <table class="mt-4 min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-3 py-3 text-left">Mois</th>
                    <th class="px-3 py-3 text-right">Entrées</th>
                    <th class="px-3 py-3 text-right">Dépenses</th>
                    <th class="px-3 py-3 text-right">Bénéfice</th>
                    @foreach (\App\Support\Finances::POSTES as $poste)
                        <th class="px-3 py-3 text-right">{{ $poste }}</th>
                    @endforeach
                    <th class="px-3 py-3 text-right">Acquisitions</th>
                    <th class="px-3 py-3 text-right">Reste à investir</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($mensuel as $ligne)
                    <tr wire:key="syn-{{ $ligne['libelle'] }}">
                        <td class="px-3 py-2.5 whitespace-nowrap font-medium">{{ $ligne['libelle'] }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $fcfa($ligne['entrees']) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $fcfa($ligne['depenses']) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap {{ $ligne['benefice'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $fcfa($ligne['benefice']) }}</td>
                        @foreach (\App\Support\Finances::POSTES as $cle => $poste)
                            <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $fcfa($ligne['repartition'][$cle]) }}</td>
                        @endforeach
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $fcfa($ligne['acquisitions']) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $fcfa($ligne['reste_a_investir']) }}</td>
                    </tr>
                @endforeach
                <tr class="bg-[#1f3864] font-semibold text-white">
                    <td class="px-3 py-3">TOTAL</td>
                    <td class="px-3 py-3 text-right whitespace-nowrap">{{ $fcfa($resultat['entrees']) }}</td>
                    <td class="px-3 py-3 text-right whitespace-nowrap">{{ $fcfa($resultat['depenses']) }}</td>
                    <td class="px-3 py-3 text-right whitespace-nowrap">{{ $fcfa($resultat['benefice']) }}</td>
                    @foreach (\App\Support\Finances::POSTES as $cle => $poste)
                        <td class="px-3 py-3 text-right whitespace-nowrap">{{ $fcfa($repartition[$cle]) }}</td>
                    @endforeach
                    <td class="px-3 py-3 text-right whitespace-nowrap">{{ $fcfa($totalAcquisitions) }}</td>
                    <td class="px-3 py-3 text-right whitespace-nowrap">{{ $fcfa($repartition['investissement'] - $totalAcquisitions) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card overflow-x-auto">
        <h2 class="p-5 pb-3 font-semibold text-slate-800">Totaux par catégorie d'activité</h2>
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">Catégorie</th>
                    <th class="px-4 py-3 text-right">Entrées</th>
                    <th class="px-4 py-3 text-right">Dépenses</th>
                    <th class="px-4 py-3 text-right">Bénéfice</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($parCategorie as $ligne)
                    <tr>
                        <td class="px-4 py-2.5">{{ $ligne['categorie'] }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $fcfa($ligne['entrees']) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $fcfa($ligne['depenses']) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $fcfa($ligne['benefice']) }}</td>
                    </tr>
                @empty
                    <x-etat-vide colspan="4" message="Aucune écriture au Journal." />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
