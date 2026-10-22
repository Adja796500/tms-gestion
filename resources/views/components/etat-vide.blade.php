@props(['colspan' => 1, 'message' => 'Aucune donnée.'])
{{-- Ligne de tableau vide illustree, a la place d'un simple texte "Aucun ...". --}}
<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-10">
        <div class="flex flex-col items-center gap-3 text-center">
            <svg viewBox="0 0 120 90" class="h-20 w-28" fill="none" aria-hidden="true">
                <ellipse cx="60" cy="80" rx="42" ry="6" fill="#e2e8f0" />
                <rect x="26" y="18" width="68" height="54" rx="8" fill="#eef2ff" stroke="#c7d2fe" stroke-width="2" />
                <rect x="36" y="30" width="34" height="5" rx="2.5" fill="#a5b4fc" />
                <rect x="36" y="41" width="48" height="4" rx="2" fill="#c7d2fe" />
                <rect x="36" y="50" width="40" height="4" rx="2" fill="#c7d2fe" />
                <circle cx="88" cy="22" r="13" fill="#10b981" />
                <path d="M82 22h12M88 16v12" stroke="#fff" stroke-width="2.5" stroke-linecap="round" />
            </svg>
            <p class="text-sm font-medium text-slate-600">{{ $message }}</p>
            @if (! $slot->isEmpty())
                <div class="text-xs text-slate-400">{{ $slot }}</div>
            @endif
        </div>
    </td>
</tr>
