@php
    $articlesEnAlerte = \App\Models\Article::all()->filter(fn ($a) => $a->estEnAlerte());
@endphp
@if ($articlesEnAlerte->isNotEmpty())
    <div class="flex items-center gap-3 overflow-hidden border-b border-amber-200 bg-amber-50 px-4 py-2 text-amber-800">
        <span class="flex flex-shrink-0 items-center gap-1.5 text-xs font-semibold uppercase tracking-wide">
            <x-nav-icon name="alert" class="h-4 w-4" />
            Stock bas
        </span>
        <div class="min-w-0 flex-1 overflow-hidden">
            <div class="flex w-max animate-marquee gap-10 whitespace-nowrap text-sm">
                @for ($i = 0; $i < 2; $i++)
                    <span class="flex items-center gap-10" aria-hidden="{{ $i === 1 ? 'true' : 'false' }}">
                        @foreach ($articlesEnAlerte as $article)
                            <span>
                                <strong>{{ $article->code }}</strong> — {{ $article->designation }}
                                : {{ $article->stock_disponible }} restant(s) (seuil {{ $article->stock_minimum }})
                            </span>
                        @endforeach
                    </span>
                @endfor
            </div>
        </div>
    </div>
@endif
