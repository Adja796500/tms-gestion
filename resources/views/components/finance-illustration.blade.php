<svg viewBox="0 0 440 380" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes->merge(['class' => 'w-full max-w-sm mx-auto']) }}>
    <defs>
        <linearGradient id="fi-card" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#818cf8" />
            <stop offset="1" stop-color="#4338ca" />
        </linearGradient>
        <linearGradient id="fi-bar" x1="0" y1="1" x2="0" y2="0">
            <stop offset="0" stop-color="#059669" />
            <stop offset="1" stop-color="#6ee7b7" />
        </linearGradient>
        <linearGradient id="fi-coin" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#fde68a" />
            <stop offset="1" stop-color="#f59e0b" />
        </linearGradient>
        <linearGradient id="fi-coin2" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#6ee7b7" />
            <stop offset="1" stop-color="#059669" />
        </linearGradient>
    </defs>

    <circle cx="90" cy="60" r="4" fill="#a5b4fc" opacity=".7" />
    <circle cx="380" cy="290" r="5" fill="#6ee7b7" opacity=".6" />
    <circle cx="360" cy="60" r="3" fill="#fde68a" opacity=".8" />

    <rect x="34" y="176" width="188" height="150" rx="22" fill="white" fill-opacity=".06" stroke="white" stroke-opacity=".12" />
    <g transform="translate(58 210)">
        <rect x="0" y="72" width="26" height="40" rx="6" fill="url(#fi-bar)" />
        <rect x="36" y="50" width="26" height="62" rx="6" fill="url(#fi-bar)" />
        <rect x="72" y="26" width="26" height="86" rx="6" fill="url(#fi-bar)" />
        <rect x="108" y="0" width="26" height="112" rx="6" fill="url(#fi-bar)" opacity=".95" />
        <path d="M2 66 L38 44 L74 20 L112 -10" stroke="#a7f3d0" stroke-width="3" stroke-linecap="round" stroke-dasharray="1 9" />
        <path d="M100 -14 L112 -10 L108 4" stroke="#a7f3d0" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none" />
    </g>

    <g transform="rotate(-9 260 140)">
        <rect x="180" y="70" width="230" height="138" rx="20" fill="url(#fi-card)" />
        <rect x="180" y="70" width="230" height="138" rx="20" fill="white" fill-opacity=".05" />
        <rect x="204" y="98" width="34" height="24" rx="5" fill="white" fill-opacity=".35" />
        <path d="M204 168c40-18 80-18 120 0" stroke="white" stroke-opacity=".3" stroke-width="3" fill="none" />
        <path d="M204 182c40-16 80-16 120 0" stroke="white" stroke-opacity=".2" stroke-width="3" fill="none" />
        <text x="204" y="152" fill="white" fill-opacity=".85" font-size="17" font-family="monospace" letter-spacing="3">•••• 8842</text>
        <circle cx="392" cy="94" r="12" fill="white" fill-opacity=".3" />
        <circle cx="378" cy="94" r="12" fill="white" fill-opacity=".55" />
    </g>

    <circle cx="352" cy="238" r="34" fill="url(#fi-coin)" />
    <circle cx="352" cy="238" r="34" fill="none" stroke="white" stroke-opacity=".4" stroke-width="2" stroke-dasharray="2 5" />
    <text x="352" y="246" text-anchor="middle" font-size="24" font-weight="700" fill="#7c2d12" fill-opacity=".75" font-family="sans-serif">$</text>

    <circle cx="308" cy="292" r="20" fill="url(#fi-coin2)" />
    <text x="308" y="298" text-anchor="middle" font-size="15" font-weight="700" fill="#064e3b" fill-opacity=".8" font-family="sans-serif">₣</text>
</svg>
