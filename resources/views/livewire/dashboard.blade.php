<div class="space-y-6">
    @php
        $user = auth()->user();
        $nomEntreprise = $user->entreprise?->nom ?? config('app.name');

        // Les 7 gros boutons de la colonne gauche de la feuille ACCUEIL, avec leurs couleurs.
        $sections = array_filter([
            [\App\Livewire\Ventes::MODULES_CAISSE, 'ventes.historique', 'Gestion des ventes', 'text-green-700'],
            ['ACHATS_STOCK', 'achats.index', 'Approvisionnement', 'text-blue-700'],
            ['ARTICLES', 'articles.index', 'Gestion des articles', 'text-red-900'],
            ['REGISTRE_DOCUMENTS', 'registre-documents.index', 'Facturation', 'text-blue-950'],
            ['CLIENTS', 'clients.index', 'Clients/Fournisseur', 'text-slate-600'],
            ['FACTURE_PROFORMA', 'factures.proforma', 'Gestion des devis', 'text-yellow-700'],
            ['SERVICES', 'services.index', 'Services', 'text-purple-700'],
        ], fn ($s) => $user->peutAccederA($s[0]));

        // Grille de boutons bleus de la feuille ACCUEIL (modules disponibles dans l'application).
        $modules = array_filter([
            ['COMPTABILITE_JOURNAL', 'comptabilite.journal.index', 'Journal', 'document'],
            ['COMPTABILITE_ACQUISITIONS', 'comptabilite.acquisitions.index', 'Acquisitions', 'banknotes'],
            ['RAPPORT MENSUEL', 'rapport-mensuel.index', 'Rapport mensuel', 'document'],
            ['STATISTIQUES', 'statistiques.index', 'Statistiques', 'trending-up'],
            ['TRESORERIE', 'tresorerie.index', 'Trésorerie', 'banknotes'],
            ['ARTICLES', 'articles.index', 'Articles/Stock', 'cube'],
            ['ACHATS_STOCK', 'achats.index', 'Achats stock', 'cart'],
            [\App\Livewire\Ventes::MODULES_CAISSE, 'ventes.index', 'Caisse', 'trending-up'],
            [\App\Livewire\Ventes::MODULES_CAISSE, 'ventes.historique', 'Historique ventes', 'clock'],
            ['PAIEMENTS_CLIENTS', 'paiements-clients.index', 'Paiements clients', 'credit-card'],
            ['REGLEMENTS_FOURNISSEURS', 'reglements-fournisseurs.index', 'Règlements fourn.', 'banknotes'],
            ['CREANCES_CLIENTS', 'creances-clients.index', 'Créances clients', 'clock'],
            ['DETTES_FOURNISSEURS', 'dettes-fournisseurs.index', 'Dettes fourn.', 'alert'],
            ['FACTURE_PROFORMA', 'factures.proforma', 'Proforma', 'document'],
            ['FACTURE_DEFINITIVE', 'factures.definitives', 'Facture', 'document'],
            ['RECU', 'recus.index', 'Reçu', 'receipt'],
            ['BORDEREAU_LIVRAISON', 'bordereaux.index', 'Bordereau', 'truck'],
            ['BON_COMMANDE', 'bons-commande.index', 'Bon de commande', 'document'],
            ['REGISTRE_DOCUMENTS', 'registre-documents.index', 'Registre', 'document'],
            ['COMMANDES', 'commandes.index', 'Commandes', 'cart'],
            ['CLIENTS', 'clients.index', 'Clients', 'users'],
            ['FOURNISSEURS', 'fournisseurs.index', 'Fournisseurs', 'truck'],
            ['ARCHIVES', 'archives.index', 'Archives', 'clock'],
            ['SERVICES', 'services.index', 'Services', 'bolt'],
            [\App\Livewire\Ventes::MODULES_CAISSE, 'monnaies-a-rendre.index', 'Monnaie à rendre', 'banknotes'],
            ['UTILISATEURS', 'utilisateurs.index', 'Utilisateurs', 'user-circle'],
            ['PARAMETRES', 'parametres.index', "Droits d'accès", 'cog'],
            ['CONFIGURATION_ENTREPRISE', 'configuration-entreprise.index', 'Paramètres', 'cog'],
            ['GUIDE', 'guide.index', 'Guide', 'receipt'],
            ['SYNTHESE', 'synthese.index', 'Synthèse', 'trending-up'],
            ['JOURNAL', 'journal.index', "Journal d'activité", 'history'],
            ['AUDIT', 'audit.index', 'Audit', 'alert'],
        ], fn ($m) => $user->peutAccederA($m[0]));
    @endphp

    <div class="overflow-hidden rounded-2xl bg-white shadow-soft ring-1 ring-slate-200">
        <div class="flex flex-wrap items-stretch">
            <div class="relative flex min-w-0 flex-1 items-center gap-6 overflow-hidden bg-gradient-to-r from-[#1f3864] via-[#26407a] to-brand-700 px-6 py-5 text-white">
                <div class="auth-bg-image pointer-events-none absolute inset-0 opacity-25"></div>
                <div class="relative min-w-0 flex-1">
                    <p class="text-sm text-amber-300">{{ now()->hour < 12 ? 'Bonjour' : (now()->hour < 18 ? 'Bon après-midi' : 'Bonsoir') }}, {{ auth()->user()->name }} 👋</p>
                    <h2 class="truncate text-xl font-bold tracking-wide sm:text-2xl">TABLEAU DE BORD — {{ $nomEntreprise }}</h2>
                    <p class="mt-0.5 text-xs italic text-white/70">Vue d'ensemble automatique. Toutes les valeurs proviennent du Journal des transactions, des ventes et des achats.</p>
                </div>
                <div class="relative hidden w-36 flex-shrink-0 drop-shadow-xl lg:block"><x-finance-illustration /></div>
            </div>
            <div class="flex w-full items-center justify-between gap-6 bg-[#1f3864] px-6 py-3 text-white sm:w-auto sm:border-l sm:border-white/10">
                <span class="text-sm font-medium text-amber-300">Date du jour</span>
                <span class="text-sm font-semibold">{{ now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[19rem_1fr]">
        @if (count($sections))
            <div class="cascade grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-1">
                @foreach ($sections as [$module, $routeName, $label, $couleur])
                    <a href="{{ route($routeName) }}" wire:navigate
                       style="font-family: 'Arial Narrow', 'Roboto Condensed', Impact, sans-serif"
                       class="block rounded-md border border-slate-300 bg-slate-50 px-3 py-3 text-center text-xl font-black uppercase leading-tight tracking-tight [overflow-wrap:anywhere] shadow-[3px_3px_0_0_rgba(100,116,139,0.45)] transition hover:-translate-y-0.5 hover:bg-white {{ $couleur }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        @endif

        @if (count($modules))
            <div class="grid self-start grid-cols-2 gap-px overflow-hidden rounded-xl bg-white sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6">
                @foreach ($modules as [$module, $routeName, $label, $icon])
                    <a href="{{ route($routeName) }}" wire:navigate
                       class="group flex items-center justify-center gap-2 bg-[#4f81bd] px-3 py-4 text-sm font-semibold text-white transition hover:bg-[#3b6aa3]">
                        <x-nav-icon :name="$icon" class="h-4 w-4 flex-shrink-0 transition group-hover:scale-125" />
                        <span class="truncate">{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @php
        $fcfaD = fn ($v) => number_format($v, 0, ',', ' ').' '.config('app.currency');
        $variation = function ($actuel, $precedent) {
            if ($precedent == 0) {
                return null;
            }
            return round(($actuel - $precedent) / abs($precedent) * 100);
        };
    @endphp

    @if (count($aTraiter))
        <div class="card p-5">
            <div class="mb-3 flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                    <x-nav-icon name="bolt" class="h-4 w-4" />
                </div>
                <h2 class="font-semibold text-slate-800">À traiter</h2>
            </div>
            <div class="cascade grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($aTraiter as $point)
                    @php $teinte = ['rose' => 'border-rose-200 bg-rose-50 text-rose-800', 'amber' => 'border-amber-200 bg-amber-50 text-amber-800', 'sky' => 'border-sky-200 bg-sky-50 text-sky-800', 'violet' => 'border-violet-200 bg-violet-50 text-violet-800'][$point['couleur']]; @endphp
                    <a href="{{ route($point['route']) }}" class="group flex items-center justify-between gap-3 rounded-xl border px-4 py-3 transition hover:shadow-md {{ $teinte }}">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold">{{ $point['label'] }}</p>
                            <p class="text-xs opacity-80">{{ $point['valeur'] }}</p>
                        </div>
                        <span class="text-lg transition group-hover:translate-x-0.5">&rarr;</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if (in_array('mois_en_cours', $widgets))
        <div class="cascade grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Entrées — '.$libelleMoisEnCours, $moisEnCours['entrees'], $moisPrecedent['entrees'], true],
                ['Dépenses du mois', $moisEnCours['depenses'], $moisPrecedent['depenses'], false],
                ['Bénéfice du mois', $moisEnCours['benefice'], $moisPrecedent['benefice'], true],
            ] as [$libelle, $actuel, $precedent, $hausseFavorable])
                @php $pct = $variation($actuel, $precedent); @endphp
                <div class="card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $libelle }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $actuel < 0 ? 'text-rose-600' : 'text-slate-900' }}" x-data="compteur(@js($fcfaD($actuel)))" x-text="affiche">{{ $fcfaD($actuel) }}</p>
                    @if ($pct !== null)
                        @php $favorable = ($pct >= 0) === $hausseFavorable; @endphp
                        <p class="mt-1 text-xs {{ $favorable ? 'text-emerald-700' : 'text-rose-600' }}">
                            {{ $pct >= 0 ? '▲' : '▼' }} {{ abs($pct) }} % <span class="text-slate-500">vs mois précédent ({{ $fcfaD($precedent) }})</span>
                        </p>
                    @else
                        <p class="mt-1 text-xs text-slate-400">Pas de données le mois précédent</p>
                    @endif
                </div>
            @endforeach
            <a href="{{ auth()->user()->peutAccederA('TRESORERIE') ? route('tresorerie.index') : '#' }}" class="card p-5 transition hover:shadow-md">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Trésorerie disponible</p>
                <p class="mt-1 text-2xl font-bold tabular-nums {{ $soldes['Total'] < 0 ? 'text-rose-600' : 'text-slate-900' }}" x-data="compteur(@js($fcfaD($soldes['Total'])))" x-text="affiche">{{ $fcfaD($soldes['Total']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Caisse {{ $fcfaD($soldes['Caisse']) }} · Banque {{ $fcfaD($soldes['Banque']) }}</p>
            </a>
        </div>
    @endif

    @if (in_array('graphiques', $widgets))
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="card p-5 xl:col-span-2">
                <h2 class="font-semibold text-slate-800">Entrées et dépenses — 12 derniers mois</h2>
                <p class="mb-4 text-xs text-slate-500">Source : Journal des transactions (ventes comprises). Survolez un mois pour le détail.</p>
                <x-graphique-evolution :evolution="$evolution" />
            </div>
            <div class="card p-5">
                <h2 class="font-semibold text-slate-800">Entrées par activité — {{ now()->year }}</h2>
                <p class="mb-4 text-xs text-slate-500">Part de chaque activité dans les entrées de l'année.</p>
                @php $totalActivites = max(1, $activites->sum('entrees')); @endphp
                <div class="space-y-3">
                    @forelse ($activites as $a)
                        <div title="{{ $a['categorie'] }} : {{ $fcfaD($a['entrees']) }}">
                            <div class="mb-1 flex justify-between gap-2 text-sm">
                                <span class="truncate text-slate-700">{{ $a['categorie'] }}</span>
                                <span class="flex-shrink-0 text-slate-500">{{ round($a['entrees'] / $totalActivites * 100) }} % · <span class="font-medium text-slate-900">{{ $fcfaD($a['entrees']) }}</span></span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full transition-all duration-1000 ease-out" x-data="{ w: 0 }" x-init="requestAnimationFrame(() => w = {{ max(1, round($a['entrees'] / $totalActivites * 100, 1)) }})" :style="'width:' + w + '%'" style="background:#2a78d6; width: {{ max(1, round($a['entrees'] / $totalActivites * 100, 1)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aucune entrée cette année.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <div class="cascade grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @if (in_array('chiffre_affaires', $widgets))
            <x-kpi-card icon="trending-up" color="emerald" label="Chiffre d'affaires"
                        value="{{ number_format($chiffreAffaires, 0, ',', ' ') }} {{ config('app.currency') }}" />
        @endif

        @if (in_array('total_achats', $widgets))
            <x-kpi-card icon="cart" color="brand" label="Total achats"
                        value="{{ number_format($totalAchats, 0, ',', ' ') }} {{ config('app.currency') }}" />
        @endif

        @if (in_array('creances', $widgets))
            <x-kpi-card icon="clock" color="amber" label="Créances clients"
                        value="{{ number_format($creances, 0, ',', ' ') }} {{ config('app.currency') }}" />
        @endif

        @if (in_array('dettes', $widgets))
            <x-kpi-card icon="alert" color="rose" label="Dettes fournisseurs"
                        value="{{ number_format($dettes, 0, ',', ' ') }} {{ config('app.currency') }}" />
        @endif

        @if (in_array('encaissements', $widgets))
            <x-kpi-card icon="credit-card" color="sky" label="Encaissements clients"
                        value="{{ number_format($totalPaiements, 0, ',', ' ') }} {{ config('app.currency') }}" />
        @endif

        @if (in_array('benefice', $widgets))
            <x-kpi-card icon="banknotes" :color="$benefice >= 0 ? 'emerald' : 'rose'" label="Bénéfice"
                        value="{{ number_format($benefice, 0, ',', ' ') }} {{ config('app.currency') }}" />
        @endif

        @if (in_array('valeur_stock', $widgets))
            <x-kpi-card icon="cube" color="violet" label="Valeur du stock"
                        value="{{ number_format($valeurStock, 0, ',', ' ') }} {{ config('app.currency') }}" />
        @endif

        @if (in_array('nb_factures', $widgets))
            <x-kpi-card icon="document" color="slate" label="Factures" value="{{ $nbFactures }}" />
        @endif

        @if (in_array('nb_articles', $widgets))
            <x-kpi-card icon="cube" color="slate" label="Articles références" value="{{ $nbArticles }}" />
        @endif

        @if (in_array('nb_clients', $widgets))
            <x-kpi-card icon="users" color="slate" label="Clients" value="{{ $nbClients }}" />
        @endif

        @if (in_array('nb_fournisseurs', $widgets))
            <x-kpi-card icon="truck" color="slate" label="Fournisseurs" value="{{ $nbFournisseurs }}" />
        @endif
    </div>

    @if (array_intersect(['alertes_stock', 'dernieres_ventes'], $widgets))
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @if (in_array('alertes_stock', $widgets))
                <div class="card p-5">
                    <div class="mb-4 flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                            <x-nav-icon name="alert" class="h-4 w-4" />
                        </div>
                        <h2 class="font-semibold text-slate-800">Alertes de stock</h2>
                    </div>
                    @if ($articlesEnAlerte->isEmpty())
                        <p class="text-sm text-slate-500">Aucune alerte, tous les stocks sont au-dessus du seuil minimum.</p>
                    @else
                        <ul class="divide-y divide-slate-100 text-sm">
                            @foreach ($articlesEnAlerte as $article)
                                <li class="flex items-center justify-between gap-3 py-2.5">
                                    <span class="truncate text-slate-700">{{ $article->code }} — {{ $article->designation }}</span>
                                    <span class="badge badge-danger flex-shrink-0">{{ $article->stock_disponible }} / min {{ $article->stock_minimum }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if (in_array('dernieres_ventes', $widgets))
                <div class="card p-5">
                    <div class="mb-4 flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                            <x-nav-icon name="trending-up" class="h-4 w-4" />
                        </div>
                        <h2 class="font-semibold text-slate-800">Dernières ventes</h2>
                    </div>
                    @if ($dernieresVentes->isEmpty())
                        <p class="text-sm text-slate-500">Aucune vente enregistrée.</p>
                    @else
                        <ul class="divide-y divide-slate-100 text-sm">
                            @foreach ($dernieresVentes as $vente)
                                @php
                                    $premiereLigne = $vente->lignes->first();
                                    $autresLignes = $vente->lignes->count() - 1;
                                @endphp
                                <li class="flex items-center justify-between gap-3 py-2.5">
                                    <span class="truncate text-slate-700">{{ $vente->date->format('d/m/Y') }} — {{ $vente->client->nom }}
                                        <span class="text-slate-400">({{ $premiereLigne?->article->designation }}{{ $autresLignes > 0 ? ' +'.$autresLignes : '' }})</span></span>
                                    <span class="flex-shrink-0 font-semibold text-slate-900">{{ number_format($vente->total, 0, ',', ' ') }} {{ config('app.currency') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @if (array_intersect(['meilleurs_clients', 'meilleurs_articles'], $widgets))
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @if (in_array('meilleurs_clients', $widgets))
                <div class="card p-5">
                    <div class="mb-4 flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-100 text-brand-600">
                            <x-nav-icon name="users" class="h-4 w-4" />
                        </div>
                        <h2 class="font-semibold text-slate-800">Meilleurs clients</h2>
                    </div>
                    @if ($meilleursClients->isEmpty())
                        <p class="text-sm text-slate-500">Aucune vente enregistrée.</p>
                    @else
                        <ul class="divide-y divide-slate-100 text-sm">
                            @foreach ($meilleursClients as $ligne)
                                <li class="flex items-center justify-between gap-3 py-2.5">
                                    <span class="flex items-center gap-2.5 truncate">
                                        <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">{{ $loop->iteration }}</span>
                                        <span class="truncate text-slate-700">{{ $ligne->client->nom ?? 'Client supprime' }}</span>
                                    </span>
                                    <span class="flex-shrink-0 font-semibold text-slate-900">{{ number_format($ligne->total_ventes, 0, ',', ' ') }} {{ config('app.currency') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if (in_array('meilleurs_articles', $widgets))
                <div class="card p-5">
                    <div class="mb-4 flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 text-violet-600">
                            <x-nav-icon name="cube" class="h-4 w-4" />
                        </div>
                        <h2 class="font-semibold text-slate-800">Meilleurs articles</h2>
                    </div>
                    @if ($meilleursArticles->isEmpty())
                        <p class="text-sm text-slate-500">Aucune vente enregistrée.</p>
                    @else
                        <ul class="divide-y divide-slate-100 text-sm">
                            @foreach ($meilleursArticles as $ligne)
                                <li class="flex items-center justify-between gap-3 py-2.5">
                                    <span class="flex items-center gap-2.5 truncate">
                                        <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-violet-50 text-xs font-bold text-violet-700">{{ $loop->iteration }}</span>
                                        @if ($ligne->article)
                                            <x-article-vignette :article="$ligne->article" taille="h-8 w-8" />
                                        @endif
                                        <span class="truncate text-slate-700">{{ $ligne->article->designation ?? 'Article supprime' }} <span class="text-slate-400">({{ $ligne->quantite_totale }} unités)</span></span>
                                    </span>
                                    <span class="flex-shrink-0 font-semibold text-slate-900">{{ number_format($ligne->total_ventes, 0, ',', ' ') }} {{ config('app.currency') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
