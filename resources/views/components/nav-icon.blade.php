@props(['name'])
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round" {{ $attributes->merge(['class' => 'h-5 w-5']) }}>
    @switch($name)
        @case('home')
            <path d="M3 10.5 12 3l9 7.5" /><path d="M5 9.5V21h5v-6h4v6h5V9.5" />
            @break
        @case('cube')
            <path d="M12 3 3 8v8l9 5 9-5V8z" /><path d="M3 8l9 5 9-5" /><path d="M12 13v8" />
            @break
        @case('cart')
            <circle cx="9" cy="20" r="1" /><circle cx="17" cy="20" r="1" /><path d="M3 4h2l2.4 12h9.2L20 7H6" />
            @break
        @case('trending-up')
            <polyline points="3 17 9 11 13 15 21 7" /><polyline points="15 7 21 7 21 13" />
            @break
        @case('users')
            <circle cx="9" cy="8" r="3" /><path d="M3 20c0-3 3-5 6-5s6 2 6 5" /><circle cx="17" cy="9" r="2.3" /><path d="M15.2 20c0-2.2 1-3.8 2.8-4.4" />
            @break
        @case('truck')
            <rect x="2" y="8" width="11" height="8" rx="1" /><path d="M13 11h4l3 3v2h-7z" /><circle cx="7" cy="18.2" r="1.6" /><circle cx="17" cy="18.2" r="1.6" />
            @break
        @case('credit-card')
            <rect x="3" y="6" width="18" height="12" rx="2" /><line x1="3" y1="10" x2="21" y2="10" />
            @break
        @case('banknotes')
            <rect x="3" y="7" width="18" height="10" rx="2" /><circle cx="12" cy="12" r="2.3" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.5" /><path d="M12 8v4l3 2" />
            @break
        @case('alert')
            <path d="M12 4 2 20h20z" /><line x1="12" y1="10" x2="12" y2="14.5" /><circle cx="12" cy="17.2" r="0.2" fill="currentColor" />
            @break
        @case('document')
            <path d="M7 3h7l4 4v14H7z" /><path d="M14 3v4h4" /><line x1="9" y1="12" x2="15" y2="12" /><line x1="9" y1="16" x2="15" y2="16" />
            @break
        @case('receipt')
            <path d="M5 3h14v18l-2-1.3-2 1.3-2-1.3-2 1.3-2-1.3-2 1.3-2-1.3z" /><line x1="8" y1="8" x2="16" y2="8" /><line x1="8" y1="12" x2="16" y2="12" />
            @break
        @case('user-circle')
            <circle cx="12" cy="12" r="9" /><circle cx="12" cy="10" r="3" /><path d="M6.3 18.5c1-2.5 3-4 5.7-4s4.7 1.5 5.7 4" />
            @break
        @case('cog')
            <circle cx="12" cy="12" r="3" /><path d="M12 2.5v3M12 18.5v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M1.5 12h3M19.5 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1" />
            @break
        @case('history')
            <path d="M3 12a9 9 0 1 0 3-6.7" /><polyline points="3 4 3 9 8 9" /><path d="M12 7v5l3.5 2" />
            @break
        @case('pencil')
            <path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
            @break
        @case('trash')
            <polyline points="3 6 5 6 21 6" /><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0-1 14a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1L6 6" /><line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" />
            @break
        @case('bolt')
            <polygon points="13 2 4 14 11 14 10 22 20 9 13 9" />
            @break
        @case('check')
            <polyline points="20 6 9 17 4 12" />
            @break
        @case('x')
            <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
            @break
    @endswitch
</svg>
