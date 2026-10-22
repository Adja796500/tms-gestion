@props(['article', 'taille' => 'h-10 w-10'])
@php
    // Photo de l'article, ou a defaut ses initiales sur une couleur stable (derivee du code).
    $teintes = ['bg-sky-100 text-sky-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-700', 'bg-violet-100 text-violet-700', 'bg-rose-100 text-rose-700', 'bg-teal-100 text-teal-700', 'bg-indigo-100 text-indigo-700'];
    $teinte = $teintes[crc32((string) ($article->code ?? $article->designation ?? '')) % count($teintes)];
    $initiales = \Illuminate\Support\Str::of($article->designation ?? '?')->explode(' ')->filter()->map(fn ($m) => mb_substr($m, 0, 1))->take(2)->implode('');
@endphp
@if ($article?->photoUrl())
    <img src="{{ $article->photoUrl() }}" alt="{{ $article->designation }}" loading="lazy"
         {{ $attributes->merge(['class' => $taille.' flex-shrink-0 rounded-lg object-cover ring-1 ring-slate-200']) }}>
@else
    <span {{ $attributes->merge(['class' => $taille.' flex flex-shrink-0 items-center justify-center rounded-lg text-xs font-bold uppercase '.$teinte]) }}>{{ $initiales }}</span>
@endif
