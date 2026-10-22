<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — {{ $title ?? 'Super-administration' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.pwa-head')
    @livewireStyles
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="bg-slate-900 text-white px-4 md:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ config('plateforme.monogramme') }}" alt="Logo" class="h-10 w-10 rounded-lg bg-white object-contain p-1 shadow-lg shadow-brand-900/40">
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-brand-300">Super-administration</p>
                    <p class="font-semibold leading-tight">{{ $title ?? 'Entreprises' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-5">
                <a href="{{ route('super-admin.entreprises') }}" class="text-xs text-slate-300 hover:text-white transition">Entreprises</a>
                <a href="{{ route('mot-de-passe.index') }}" class="text-xs text-slate-300 hover:text-white transition">Changer mon mot de passe</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-slate-300 hover:text-white transition">Se déconnecter</button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-4 md:p-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
