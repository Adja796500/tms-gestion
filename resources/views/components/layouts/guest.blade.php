<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Connexion</title>
    @include('partials.pwa-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-950 antialiased">
    <div class="flex min-h-screen">
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden px-12 py-12 lg:flex">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute inset-0 bg-gradient-to-br from-brand-950 via-emerald-950 to-slate-950"></div>
                <div class="auth-bg-image absolute inset-0"></div>
                <div class="absolute -top-32 -left-24 h-96 w-96 rounded-full bg-brand-600/30 blur-3xl"></div>
                <div class="absolute -bottom-32 -right-16 h-[26rem] w-[26rem] rounded-full bg-emerald-500/25 blur-3xl"></div>
            </div>

            <div class="relative">
                <div class="inline-flex rounded-2xl bg-white px-5 py-3 shadow-xl shadow-black/30 ring-1 ring-white/20">
                    <img src="{{ config('plateforme.logo') }}" alt="{{ config('plateforme.nom') }}" class="h-16 w-auto">
                </div>
            </div>

            <div class="relative">
                <x-finance-illustration class="mx-auto w-full max-w-md" />
            </div>

            <div class="relative">
                <h2 class="max-w-md text-3xl font-semibold leading-tight text-white">
                    Pilotez vos finances et votre stock en toute confiance.
                </h2>
                <p class="mt-3 max-w-sm text-sm text-slate-300">
                    Ventes, achats, créances, dettes et trésorerie réunis dans une seule plateforme, avec des
                    droits d'accès adaptés à chaque profil.
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-emerald-200 ring-1 ring-white/10">
                        <x-nav-icon name="banknotes" class="h-3.5 w-3.5" /> Suivi financier
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-brand-200 ring-1 ring-white/10">
                        <x-nav-icon name="cube" class="h-3.5 w-3.5" /> Stock en temps réel
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-amber-200 ring-1 ring-white/10">
                        <x-nav-icon name="receipt" class="h-3.5 w-3.5" /> Factures & reçus
                    </span>
                </div>
            </div>
        </div>

        <div class="relative flex w-full flex-1 items-center justify-center overflow-hidden bg-slate-100 px-4 py-10 lg:w-1/2">
            <div class="pointer-events-none absolute inset-0 lg:hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-brand-950/90 to-slate-950"></div>
                <div class="auth-bg-image absolute inset-0"></div>
                <div class="absolute -top-32 -left-24 h-96 w-96 rounded-full bg-emerald-500/25 blur-3xl"></div>
            </div>

            <div class="relative w-full max-w-md">
                <div class="mb-7 text-center lg:hidden">
                    <div class="mx-auto mb-4 inline-flex rounded-2xl bg-white px-4 py-2.5 shadow-lg shadow-brand-900/50">
                        <img src="{{ config('plateforme.logo') }}" alt="{{ config('plateforme.nom') }}" class="h-14 w-auto">
                    </div>
                    <p class="mb-1 text-xs uppercase tracking-[0.2em] text-brand-300">Plateforme de gestion</p>
                    <h1 class="text-2xl font-semibold text-white">{{ config('app.name') }}</h1>
                </div>

                <div class="rounded-2xl bg-white p-8 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-900/5 lg:shadow-lg">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-slate-400 lg:text-slate-500">© {{ now()->year }} {{ config('app.name') }}</p>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
