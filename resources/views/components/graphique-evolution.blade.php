@props(['evolution'])
@php
    // Barres groupees Entrees / Depenses sur 12 mois, un seul axe (FCFA).
    // Couleurs : categorielles 1 et 2 de la palette validee (bleu / orange).
    $largeur = 720;
    $hauteur = 240;
    $gauche = 58;
    $bas = 26;
    $haut = 12;
    $zoneH = $hauteur - $bas - $haut;
    $max = max(1, $evolution->max(fn ($m) => max($m['entrees'], $m['depenses'])));
    // Graduation "ronde" de l'axe
    $pas = pow(10, floor(log10($max)));
    foreach ([1, 2, 2.5, 5, 10] as $m) {
        if ($max / ($pas * $m) <= 4) { $pas *= $m; break; }
    }
    $echelle = $pas * ceil($max / $pas);
    $n = max(1, $evolution->count());
    $colonne = ($largeur - $gauche) / $n;
    $barre = min(18, max(4, ($colonne - 10) / 2));
    $y = fn ($v) => $haut + $zoneH - ($v / $echelle) * $zoneH;
    $court = fn ($v) => $v >= 1_000_000 ? rtrim(rtrim(number_format($v / 1_000_000, 1, ',', ''), '0'), ',').' M' : ($v >= 1000 ? round($v / 1000).' k' : (string) round($v));
    $fcfa = fn ($v) => number_format($v, 0, ',', ' ').' '.config('app.currency');
    $points = $evolution->map(fn ($m) => [
        'mois' => $m['libelle'],
        'entrees' => $fcfa($m['entrees']),
        'depenses' => $fcfa($m['depenses']),
        'benefice' => $fcfa($m['benefice']),
        'negatif' => $m['benefice'] < 0,
    ])->values();
@endphp
<div x-data="{ survol: null, tableau: false, points: @js($points) }" class="relative">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-4 text-xs text-slate-600">
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm" style="background:#2a78d6"></span>Entrées</span>
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm" style="background:#eb6834"></span>Dépenses</span>
        </div>
        <button type="button" @click="tableau = !tableau" class="text-xs font-medium text-brand-600 hover:underline" x-text="tableau ? 'Voir le graphique' : 'Voir le tableau'"></button>
    </div>

    <div x-show="!tableau" class="relative">
        <svg viewBox="0 0 {{ $largeur }} {{ $hauteur }}" class="h-auto w-full" role="img" aria-label="Entrées et dépenses par mois">
            @for ($i = 0; $i <= $echelle / $pas; $i++)
                @php($v = $i * $pas)
                <line x1="{{ $gauche }}" x2="{{ $largeur }}" y1="{{ $y($v) }}" y2="{{ $y($v) }}" stroke="#e2e8f0" stroke-width="1" />
                <text x="{{ $gauche - 8 }}" y="{{ $y($v) + 4 }}" text-anchor="end" font-size="11" fill="#64748b">{{ $court($v) }}</text>
            @endfor
            @foreach ($evolution as $i => $m)
                @php($cx = $gauche + $colonne * $i + $colonne / 2)
                <g :opacity="survol === null || survol === {{ $i }} ? 1 : 0.45">
                    @if ($m['entrees'] > 0)
                        <path class="barre-graph" style="animation-delay: {{ $i * 40 }}ms" d="M{{ $cx - $barre - 1 }},{{ $y(0) }} V{{ $y($m['entrees']) + 3 }} q0,-3 3,-3 h{{ $barre - 6 }} q3,0 3,3 V{{ $y(0) }} Z" fill="#2a78d6" />
                    @endif
                    @if ($m['depenses'] > 0)
                        <path class="barre-graph" style="animation-delay: {{ $i * 40 + 20 }}ms" d="M{{ $cx + 1 }},{{ $y(0) }} V{{ $y($m['depenses']) + 3 }} q0,-3 3,-3 h{{ $barre - 6 }} q3,0 3,3 V{{ $y(0) }} Z" fill="#eb6834" />
                    @endif
                </g>
                <text x="{{ $cx }}" y="{{ $hauteur - 8 }}" text-anchor="middle" font-size="11" fill="#64748b">{{ \App\Support\Finances::moisCourt($m['mois']) }}</text>
                {{-- Zone de survol plus large que les barres --}}
                <rect x="{{ $gauche + $colonne * $i }}" y="0" width="{{ $colonne }}" height="{{ $hauteur - $bas }}" fill="transparent"
                      @mouseenter="survol = {{ $i }}" @mouseleave="survol = null" @click="survol = {{ $i }}" />
            @endforeach
            <line x1="{{ $gauche }}" x2="{{ $largeur }}" y1="{{ $y(0) }}" y2="{{ $y(0) }}" stroke="#94a3b8" stroke-width="1" />
        </svg>

        <template x-if="survol !== null">
            <div class="pointer-events-none absolute top-2 z-10 w-52 rounded-lg border border-slate-200 bg-white p-3 text-xs shadow-lg"
                 :style="'left:' + Math.min(Math.max(0, ((survol + 0.5) / points.length) * 100 - 10), 62) + '%'">
                <p class="mb-1.5 font-semibold text-slate-900" x-text="points[survol].mois"></p>
                <p class="flex justify-between gap-3"><span class="flex items-center gap-1.5 text-slate-600"><span class="h-2 w-2 rounded-sm" style="background:#2a78d6"></span>Entrées</span><span class="font-medium text-slate-900" x-text="points[survol].entrees"></span></p>
                <p class="flex justify-between gap-3"><span class="flex items-center gap-1.5 text-slate-600"><span class="h-2 w-2 rounded-sm" style="background:#eb6834"></span>Dépenses</span><span class="font-medium text-slate-900" x-text="points[survol].depenses"></span></p>
                <p class="mt-1.5 flex justify-between gap-3 border-t border-slate-100 pt-1.5"><span class="text-slate-600">Bénéfice</span><span class="font-semibold" :class="points[survol].negatif ? 'text-rose-600' : 'text-slate-900'" x-text="points[survol].benefice"></span></p>
            </div>
        </template>
    </div>

    <div x-show="tableau" x-cloak class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr><th class="px-3 py-2 text-left">Mois</th><th class="px-3 py-2 text-right">Entrées</th><th class="px-3 py-2 text-right">Dépenses</th><th class="px-3 py-2 text-right">Bénéfice</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($evolution as $m)
                    <tr>
                        <td class="px-3 py-2">{{ $m['libelle'] }}</td>
                        <td class="px-3 py-2 text-right">{{ $fcfa($m['entrees']) }}</td>
                        <td class="px-3 py-2 text-right">{{ $fcfa($m['depenses']) }}</td>
                        <td class="px-3 py-2 text-right {{ $m['benefice'] < 0 ? 'text-rose-600' : '' }}">{{ $fcfa($m['benefice']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
