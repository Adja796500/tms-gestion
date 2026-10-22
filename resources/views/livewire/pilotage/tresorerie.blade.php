@php($fcfa = fn ($v) => number_format($v, 0, ',', ' ').' '.config('app.currency'))
<div class="space-y-6">
    <p class="text-xs italic text-slate-500">
        Soldes de caisse et de banque calculés automatiquement : soldes initiaux (Configuration entreprise)
        + entrées (Journal, paiements clients) − sorties (Journal, acquisitions, achats réglés, règlements fournisseurs).
    </p>

    <div class="cascade grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-kpi-card icon="banknotes" color="emerald" label="Solde de caisse" value="{{ $fcfa($soldes['Caisse']) }}" />
        <x-kpi-card icon="credit-card" color="sky" label="Solde bancaire" value="{{ $fcfa($soldes['Banque']) }}" />
        <x-kpi-card icon="trending-up" :color="$soldes['Total'] >= 0 ? 'brand' : 'rose'" label="Trésorerie totale" value="{{ $fcfa($soldes['Total']) }}" />
    </div>

    <div class="card p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-slate-800">Vue journalière</h2>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                Date à analyser :
                <input type="date" wire:model.live="dateAnalyse" class="input-field">
            </label>
        </div>
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left"></th>
                    <th class="px-4 py-3 text-right">Caisse</th>
                    <th class="px-4 py-3 text-right">Banque</th>
                    <th class="px-4 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3 font-medium">Entrées</td>
                    <td class="px-4 py-3 text-right">{{ $fcfa($fluxJour['Caisse']['entrees']) }}</td>
                    <td class="px-4 py-3 text-right">{{ $fcfa($fluxJour['Banque']['entrees']) }}</td>
                    <td class="px-4 py-3 text-right">{{ $fcfa($fluxJour['Caisse']['entrees'] + $fluxJour['Banque']['entrees']) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 font-medium">Sorties</td>
                    <td class="px-4 py-3 text-right">{{ $fcfa($fluxJour['Caisse']['sorties']) }}</td>
                    <td class="px-4 py-3 text-right">{{ $fcfa($fluxJour['Banque']['sorties']) }}</td>
                    <td class="px-4 py-3 text-right">{{ $fcfa($fluxJour['Caisse']['sorties'] + $fluxJour['Banque']['sorties']) }}</td>
                </tr>
                <tr class="bg-slate-50 font-semibold">
                    <td class="px-4 py-3">Solde du jour</td>
                    <td class="px-4 py-3 text-right">{{ $fcfa($fluxJour['Caisse']['entrees'] - $fluxJour['Caisse']['sorties']) }}</td>
                    <td class="px-4 py-3 text-right">{{ $fcfa($fluxJour['Banque']['entrees'] - $fluxJour['Banque']['sorties']) }}</td>
                    <td class="px-4 py-3 text-right">{{ $fcfa($fluxJour['Caisse']['entrees'] + $fluxJour['Banque']['entrees'] - $fluxJour['Caisse']['sorties'] - $fluxJour['Banque']['sorties']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card overflow-x-auto">
        <div class="p-5 pb-0">
            <h2 class="font-semibold text-slate-800">Synthèse mensuelle (Caisse + Banque)</h2>
            <p class="text-xs text-slate-500">Solde cumulé de départ : {{ $fcfa($soldesInitiaux['Caisse'] + $soldesInitiaux['Banque']) }} (soldes initiaux).</p>
        </div>
        <table class="mt-4 min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-3 py-3 text-left">Mois</th>
                    <th class="px-3 py-3 text-right">Entrées Caisse</th>
                    <th class="px-3 py-3 text-right">Sorties Caisse</th>
                    <th class="px-3 py-3 text-right">Entrées Banque</th>
                    <th class="px-3 py-3 text-right">Sorties Banque</th>
                    <th class="px-3 py-3 text-right">Total Entrées</th>
                    <th class="px-3 py-3 text-right">Total Sorties</th>
                    <th class="px-3 py-3 text-right">Solde du mois</th>
                    <th class="px-3 py-3 text-right">Solde cumulé</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($mensuel as $ligne)
                    <tr wire:key="tres-{{ $ligne['libelle'] }}">
                        <td class="px-3 py-2.5 whitespace-nowrap font-medium">{{ $ligne['libelle'] }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $fcfa($ligne['flux']['Caisse']['entrees']) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $fcfa($ligne['flux']['Caisse']['sorties']) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $fcfa($ligne['flux']['Banque']['entrees']) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $fcfa($ligne['flux']['Banque']['sorties']) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $fcfa($ligne['total_entrees_tresorerie']) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $fcfa($ligne['total_sorties_tresorerie']) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap {{ $ligne['solde_mois'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $fcfa($ligne['solde_mois']) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap font-semibold">{{ $fcfa($ligne['solde_cumule']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
