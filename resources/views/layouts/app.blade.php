<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — {{ $title ?? 'Plateforme de gestion' }}</title>
    @include('partials.pwa-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-100 text-slate-800 antialiased" x-data="{ menuMobile: false }" @keydown.escape.window="menuMobile = false">
    @php
        $user = auth()->user();
        $nomEntreprise = $user?->entreprise?->nom ?? config('app.name');
        // Logo propre a l'entreprise de l'utilisateur (requete explicite : le super-
        // administrateur n'est rattache a aucune entreprise et ne doit pas voir celui d'une autre).
        $logoEntreprise = $user?->entreprise_id
            ? \App\Models\ParametreEntreprise::withoutGlobalScopes()->where('entreprise_id', $user->entreprise_id)->first()?->logoUrl()
            : null;

        // Menu range par metier (le classeur avait une feuille par module, a plat) :
        // chaque groupe ne s'affiche que s'il contient au moins un module autorise.
        $groupesMenu = [
            ['Accueil', [
                ['ACCUEIL', 'dashboard', 'Tableau de bord', 'home'],
            ]],
            ['Ventes & clients', [
                [\App\Livewire\Ventes::MODULES_CAISSE, 'ventes.index', 'Caisse / Nouvelle vente', 'trending-up'],
                [\App\Livewire\Ventes::MODULES_CAISSE, 'ventes.historique', 'Historique des ventes', 'clock'],
                ['SERVICES', 'services.index', 'Services', 'bolt'],
                ['CLIENTS', 'clients.index', 'Clients', 'users'],
                ['COMMANDES', 'commandes.index', 'Commandes clients', 'cart'],
                ['PAIEMENTS_CLIENTS', 'paiements-clients.index', 'Paiements clients', 'credit-card'],
                ['CREANCES_CLIENTS', 'creances-clients.index', 'Créances clients', 'clock'],
                ['CREANCES_CLIENTS', 'relances.index', 'Relances clients', 'alert'],
                [\App\Livewire\Ventes::MODULES_CAISSE, 'monnaies-a-rendre.index', 'Monnaie à rendre', 'banknotes'],
            ]],
            ['Achats & stock', [
                ['ARTICLES', 'articles.index', 'Articles & stock', 'cube'],
                ['ACHATS_STOCK', 'achats.index', 'Achats', 'cart'],
                ['FOURNISSEURS', 'fournisseurs.index', 'Fournisseurs', 'truck'],
                ['REGLEMENTS_FOURNISSEURS', 'reglements-fournisseurs.index', 'Règlements fournisseurs', 'banknotes'],
                ['DETTES_FOURNISSEURS', 'dettes-fournisseurs.index', 'Dettes fournisseurs', 'alert'],
            ]],
            ['Facturation', [
                ['REGISTRE_DOCUMENTS', 'registre-documents.index', 'Registre des documents', 'document'],
                ['FACTURE_PROFORMA', 'factures.proforma', 'Proformas / devis', 'document'],
                ['FACTURE_DEFINITIVE', 'factures.definitives', 'Factures', 'document'],
                ['RECU', 'recus.index', 'Reçus', 'receipt'],
                ['BORDEREAU_LIVRAISON', 'bordereaux.index', 'Bordereaux de livraison', 'truck'],
                ['BON_COMMANDE', 'bons-commande.index', 'Bons de commande', 'cart'],
            ]],
            ['Finances', [
                ['COMPTABILITE_JOURNAL', 'comptabilite.journal.index', 'Journal des transactions', 'document'],
                ['TRESORERIE', 'tresorerie.index', 'Trésorerie', 'banknotes'],
                ['RAPPORT MENSUEL', 'rapport-mensuel.index', 'Rapport mensuel', 'document'],
                ['SYNTHESE', 'synthese.index', 'Synthèse', 'trending-up'],
                ['STATISTIQUES', 'statistiques.index', 'Statistiques', 'trending-up'],
                ['COMPTABILITE_ACQUISITIONS', 'comptabilite.acquisitions.index', 'Acquisitions & prêts', 'banknotes'],
            ]],
            ['Administration', [
                ['UTILISATEURS', 'utilisateurs.index', 'Utilisateurs', 'user-circle'],
                ['PARAMETRES', 'parametres.index', 'Paramètres & droits', 'cog'],
                ['CONFIGURATION_ENTREPRISE', 'configuration-entreprise.index', 'Configuration entreprise', 'cog'],
                ['CATEGORIES_ACTIVITE', 'categories-activite.index', "Catégories d'activité", 'receipt'],
                ['ARCHIVES', 'archives.index', 'Archives & sauvegardes', 'clock'],
                ['JOURNAL', 'journal.index', "Journal d'activité", 'history'],
                ['AUDIT', 'audit.index', 'Audit', 'alert'],
                ['GUIDE', 'guide.index', "Guide d'utilisation", 'receipt'],
            ]],
        ];

        $groupesMenu = collect($groupesMenu)
            ->map(fn ($g) => [$g[0], array_values(array_filter($g[1], fn ($l) => $user && $user->peutAccederA($l[0])))])
            ->filter(fn ($g) => count($g[1]) > 0)
            ->values();
    @endphp

    <div id="barre-chargement" class="print:hidden"></div>

    {{-- Bandeau affiche quand la connexion internet est perdue --}}
    <div x-data="{ enLigne: navigator.onLine }" @online.window="enLigne = true" @offline.window="enLigne = false"
         x-show="!enLigne" x-cloak x-transition
         class="fixed inset-x-0 bottom-0 z-[75] flex items-center justify-center gap-2 bg-amber-500 px-4 py-2 text-sm font-medium text-white shadow-lg print:hidden">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2 2l20 20M8.5 16.5a5 5 0 0 1 7 0M5 13a10 10 0 0 1 5.17-2.8M19 13a10 10 0 0 0-2.3-1.6M12 20h.01" /></svg>
        Connexion perdue : vous consultez la dernière version enregistrée. Les enregistrements reprendront au retour du réseau.
    </div>

    {{-- Fond sombre derriere le menu sur telephone --}}
    <div x-show="menuMobile" x-cloak x-transition.opacity @click="menuMobile = false" class="fixed inset-0 z-30 bg-slate-900/50 md:hidden print:hidden"></div>

    <div class="min-h-screen flex">
        <aside :class="menuMobile ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-gradient-to-b from-slate-900 to-brand-950 text-slate-200 transition-transform duration-200 md:sticky md:top-0 md:h-screen md:w-64 md:translate-x-0 md:flex-shrink-0 print:hidden">
            <div class="px-5 py-5 border-b border-white/10 flex items-center gap-3">
                @if ($logoEntreprise)
                    <img src="{{ $logoEntreprise }}" alt="Logo" class="h-10 w-10 flex-shrink-0 rounded-lg bg-white object-contain p-0.5 shadow-lg shadow-brand-900/40">
                @else
                    {{-- Pas encore de logo : initiales de l'entreprise (le logo se depose dans Configuration entreprise). --}}
                    <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-brand-900/40">
                        {{ Str::of($nomEntreprise)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] uppercase tracking-widest text-brand-300">Plateforme</p>
                    <p class="font-semibold leading-tight text-white truncate">{{ $nomEntreprise }}</p>
                </div>
                <button type="button" @click="menuMobile = false" class="text-slate-400 hover:text-white md:hidden" aria-label="Fermer le menu">
                    <x-nav-icon name="x" class="h-5 w-5" />
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto py-3 text-sm">
                @foreach ($groupesMenu as [$titreGroupe, $liens])
                    @php($groupeActif = collect($liens)->contains(fn ($l) => request()->routeIs($l[1])))
                    <div x-data="{ ouvert: {{ $groupeActif || $loop->first ? 'true' : 'false' }} }" class="mb-1">
                        @if ($titreGroupe !== 'Accueil')
                            <button type="button" @click="ouvert = !ouvert"
                                    class="mx-2.5 mt-2 flex w-[calc(100%-1.25rem)] items-center justify-between rounded-md px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400 hover:text-white">
                                <span>{{ $titreGroupe }}</span>
                                <svg :class="ouvert ? 'rotate-90' : ''" class="h-3.5 w-3.5 transition-transform" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" /></svg>
                            </button>
                        @endif
                        <div x-show="ouvert" {{ $groupeActif ? '' : 'x-cloak' }} class="space-y-0.5">
                            @foreach ($liens as [$module, $routeName, $label, $icon])
                                @php($active = request()->routeIs($routeName))
                                <a href="{{ route($routeName) }}" wire:navigate
                                   class="mx-2.5 flex items-center gap-3 rounded-lg px-3 py-2 transition {{ $active ? 'bg-white/10 text-white font-medium shadow-inner' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                    <x-nav-icon :name="$icon" class="h-[18px] w-[18px] flex-shrink-0 {{ $active ? 'text-brand-300' : 'text-slate-400' }}" />
                                    <span class="truncate">{{ $label }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
            <div class="px-5 py-4 border-t border-white/10">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full bg-brand-700 flex items-center justify-center text-xs font-semibold text-white flex-shrink-0">
                        {{ mb_substr($user->name ?? '?', 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-slate-100 font-medium text-sm truncate">{{ $user->name ?? '' }}</p>
                        <p class="text-xs text-brand-300 truncate">{{ $user->profil ?? '' }}</p>
                    </div>
                </div>
                <a href="{{ route('mon-role.index') }}" class="mt-3 flex items-center gap-1.5 text-xs {{ request()->routeIs('mon-role.index') ? 'text-white font-medium' : 'text-slate-400 hover:text-white' }} transition">
                    <x-nav-icon name="user-circle" class="h-3.5 w-3.5" />
                    Mon rôle — ce que je peux faire
                </a>
                <a href="{{ route('mot-de-passe.index') }}" class="mt-2 flex items-center gap-1.5 text-xs {{ request()->routeIs('mot-de-passe.index') ? 'text-white font-medium' : 'text-slate-400 hover:text-white' }} transition">
                    <x-nav-icon name="cog" class="h-3.5 w-3.5" />
                    Changer le mot de passe
                </a>
                <button type="button" x-data="{ dispo: !!window.__invitationInstallation }" x-show="dispo" x-cloak
                        @installation-disponible.window="dispo = true" @click="window.installerApplication(); dispo = false"
                        class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500/90 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-500">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14" /></svg>
                    Installer l'application
                </button>
                <form method="POST" action="{{ route('logout') }}" class="mt-3" @submit="navigator.serviceWorker?.controller?.postMessage('vider-pages')">
                    @csrf
                    <button type="submit" class="w-full text-left text-xs text-slate-400 hover:text-white transition flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" /><path d="M10 17l5-5-5-5" /><path d="M15 12H3" />
                        </svg>
                        Se déconnecter
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="sticky top-0 z-20 bg-white/80 backdrop-blur border-b border-slate-200 print:hidden">
                <div class="h-0.5 bg-gradient-to-r from-brand-500 via-sky-400 to-emerald-400"></div>
                <div class="flex items-center justify-between gap-3 px-4 md:px-8 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" @click="menuMobile = true" class="-ml-1 rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 md:hidden" aria-label="Ouvrir le menu">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                        <h1 class="truncate text-lg md:text-xl font-semibold text-slate-900">{{ $title ?? 'Plateforme de gestion' }}</h1>
                    </div>
                    <span class="flex flex-shrink-0 items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500" x-data="horloge">
                        <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span></span>
                        {{ now()->format('d/m/Y') }} <span class="hidden tabular-nums sm:inline" x-text="heure"></span>
                    </span>
                </div>
                @if ($user && $user->peutAccederA('ARTICLES'))
                    <x-stock-ticker />
                @endif
            </header>

            <main class="flex-1 p-4 md:p-8 print:p-0">
                <div class="mb-4 hidden border-b border-slate-300 pb-2 print:block">
                    <p class="text-lg font-bold">{{ $nomEntreprise }} — {{ $title ?? '' }}</p>
                    <p class="text-xs text-slate-500">Imprimé le {{ now()->format('d/m/Y à H:i') }} par {{ $user->name ?? '' }}</p>
                </div>
                @if (session('status'))
                    <div wire:key="toast-{{ Str::random(8) }}" x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)"
                         x-show="show" x-transition x-cloak
                         class="fixed left-4 right-4 top-20 z-50 flex items-start gap-3 rounded-xl border border-emerald-200 bg-white px-4 py-3.5 text-sm text-emerald-800 shadow-xl shadow-emerald-900/10 sm:left-auto sm:w-full sm:max-w-sm">
                        <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <x-nav-icon name="check" class="h-3.5 w-3.5" />
                        </div>
                        <span class="min-w-0 flex-1 break-words pt-0.5">{{ session('status') }}</span>
                        <button type="button" @click="show = false" class="ml-auto flex-shrink-0 text-emerald-400 hover:text-emerald-600">
                            <x-nav-icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                @endif

                <div class="apparition">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
