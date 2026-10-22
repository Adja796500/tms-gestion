@props(['icon', 'label', 'value', 'color' => 'brand'])
@php
    // Liseré de couleur a gauche, fond legerement teinte et icone en degrade.
    $colors = [
        'emerald' => ['accent' => 'before:bg-emerald-500', 'fond' => 'from-emerald-50/80', 'icone' => 'from-emerald-400 to-emerald-600 shadow-emerald-500/30'],
        'brand'   => ['accent' => 'before:bg-brand-500', 'fond' => 'from-brand-50/80', 'icone' => 'from-brand-400 to-brand-600 shadow-brand-500/30'],
        'amber'   => ['accent' => 'before:bg-amber-500', 'fond' => 'from-amber-50/80', 'icone' => 'from-amber-400 to-amber-600 shadow-amber-500/30'],
        'rose'    => ['accent' => 'before:bg-rose-500', 'fond' => 'from-rose-50/80', 'icone' => 'from-rose-400 to-rose-600 shadow-rose-500/30'],
        'sky'     => ['accent' => 'before:bg-sky-500', 'fond' => 'from-sky-50/80', 'icone' => 'from-sky-400 to-sky-600 shadow-sky-500/30'],
        'violet'  => ['accent' => 'before:bg-violet-500', 'fond' => 'from-violet-50/80', 'icone' => 'from-violet-400 to-violet-600 shadow-violet-500/30'],
        'slate'   => ['accent' => 'before:bg-slate-400', 'fond' => 'from-slate-50', 'icone' => 'from-slate-400 to-slate-600 shadow-slate-500/30'],
    ];
    $c = $colors[$color] ?? $colors['brand'];
@endphp
<div {{ $attributes->merge(['class' => "card relative flex items-start gap-4 overflow-hidden bg-gradient-to-br {$c['fond']} to-white p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg before:absolute before:inset-y-0 before:left-0 before:w-1 {$c['accent']}"]) }}>
    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br text-white shadow-lg {{ $c['icone'] }}">
        <x-nav-icon :name="$icon" class="h-5 w-5" />
    </div>
    <div class="min-w-0">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
        <p wire:key="kpi-{{ md5($label.$value) }}" class="mt-1 truncate text-xl font-bold tabular-nums text-slate-900" x-data="compteur(@js($value))" x-text="affiche">{{ $value }}</p>
    </div>
</div>
