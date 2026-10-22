@php($fcfa = fn ($v) => number_format($v, 0, ',', ' ').' '.config('app.currency'))
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
            Mois à analyser :
            <select wire:model.live="mois" class="input-field w-56">
                @foreach ($moisDisponibles as $m)
                    <option value="{{ $m->format('Y-m') }}">{{ \App\Support\Finances::libelleMois($m) }}</option>
                @endforeach
            </select>
        </label>
        <button type="button" onclick="window.print()" class="btn-ghost">Imprimer le rapport</button>
    </div>
    <p class="text-xs italic text-slate-500">Choisissez un mois : tout le rapport se recalcule automatiquement à partir du Journal et des Acquisitions.</p>

    <div class="cascade grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-kpi-card icon="trending-up" color="emerald" label="Entrées — {{ $libelleMois }}" value="{{ $fcfa($resultat['entrees']) }}" />
        <x-kpi-card icon="cart" color="rose" label="Dépenses" value="{{ $fcfa($resultat['depenses']) }}" />
        <x-kpi-card icon="banknotes" :color="$resultat['benefice'] >= 0 ? 'brand' : 'rose'" label="Bénéfice" value="{{ $fcfa($resultat['benefice']) }}" />
    </div>

    <div class="card overflow-x-auto">
        <h2 class="p-5 pb-3 font-semibold text-slate-800">Détail par catégorie d'activité</h2>
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
                @foreach ($categories as $ligne)
                    <tr>
                        <td class="px-4 py-2.5">{{ $ligne['categorie'] }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $fcfa($ligne['entrees']) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $fcfa($ligne['depenses']) }}</td>
                        <td class="px-4 py-2.5 text-right {{ $ligne['benefice'] < 0 ? 'text-rose-600' : '' }}">{{ $fcfa($ligne['benefice']) }}</td>
                    </tr>
                @endforeach
                <tr class="bg-[#1f3864] font-semibold text-white">
                    <td class="px-4 py-3">TOTAL</td>
                    <td class="px-4 py-3 text-right">{{ $fcfa($resultat['entrees']) }}</td>
                    <td class="px-4 py-3 text-right">{{ $fcfa($resultat['depenses']) }}</td>
                    <td class="px-4 py-3 text-right">{{ $fcfa($resultat['benefice']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="card overflow-x-auto">
            <h2 class="p-5 pb-3 font-semibold text-slate-800">Répartition automatique du bénéfice total</h2>
            <table class="min-w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-4 py-3 text-left">Poste</th>
                        <th class="px-4 py-3 text-right">Taux</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach (\App\Support\Finances::POSTES as $cle => $poste)
                        <tr>
                            <td class="px-4 py-2.5">{{ $poste }}</td>
                            <td class="px-4 py-2.5 text-right">{{ number_format($taux[$cle] * 100, 0) }} %</td>
                            <td class="px-4 py-2.5 text-right">{{ $fcfa($repartition[$cle]) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card overflow-x-auto">
            <h2 class="p-5 pb-3 font-semibold text-slate-800">Acquisitions, prêts sur bénéfices & réserve</h2>
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    <tr><td class="px-4 py-2.5">Total Acquisitions (matériel)</td><td class="px-4 py-2.5 text-right">{{ $fcfa($acquisitions['Acquisition']) }}</td></tr>
                    <tr><td class="px-4 py-2.5">Total Prêts Bénéfices</td><td class="px-4 py-2.5 text-right">{{ $fcfa($acquisitions['Pret_Benefices']) }}</td></tr>
                    <tr><td class="px-4 py-2.5">Total Dépenses réserve</td><td class="px-4 py-2.5 text-right">{{ $fcfa($acquisitions['Depense_Reserve']) }}</td></tr>
                    <tr class="bg-slate-50 font-semibold"><td class="px-4 py-2.5">Reste à investir</td><td class="px-4 py-2.5 text-right {{ $resteAInvestir < 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $fcfa($resteAInvestir) }}</td></tr>
                    <tr class="bg-slate-50 font-semibold"><td class="px-4 py-2.5">Reste sur réserve (Épargne)</td><td class="px-4 py-2.5 text-right {{ $resteReserve < 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $fcfa($resteReserve) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
