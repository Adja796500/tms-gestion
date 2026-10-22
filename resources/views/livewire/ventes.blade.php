<div class="space-y-6" x-data="{ recuUrl: null }"
     x-on:vente-enregistree.window="recuUrl = '{{ url('/ventes') }}/' + $event.detail.venteId + '/imprimer?print=1'">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl font-semibold text-slate-800">Ventes</h1>
        <a href="{{ route('ventes.historique') }}" class="btn-ghost inline-flex items-center gap-1.5">
            <x-nav-icon name="clock" class="h-4 w-4" />
            Historique de vente
        </a>
    </div>

    <div class="lg:grid lg:grid-cols-2 lg:gap-6 lg:items-start space-y-6 lg:space-y-0">
        <div class="lg:col-span-1">
            @if ($showForm)
                <div class="card p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        </span>
                        <h2 class="font-semibold text-slate-800">{{ $editingId ? 'Modifier la vente — panier' : 'Nouvelle vente — panier' }}</h2>
                    </div>

                    <form wire:submit="enregistrer" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="label-field">Date</label>
                                <input type="date" wire:model="date" class="mt-1 w-full input-field">
                                @error('date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label-field">Client</label>
                                <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                                    <input type="text" wire:model.live.debounce.300ms="clientRecherche"
                                           placeholder="Rechercher ou saisir un nouveau client..." autocomplete="off"
                                           x-on:focus="open = true; $el.select()"
                                           x-on:input="open = true"
                                           x-on:keydown.escape="open = false"
                                           class="mt-1 w-full input-field">

                                    <div x-show="open" x-cloak
                                         class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg">
                                        {{-- Une fois un client confirme, le texte du champ est son nom exact : le filtrer
                                             dessus ne remonterait que lui-meme. On affiche alors la liste complete pour
                                             pouvoir en choisir un autre. --}}
                                        @forelse ($this->clientsPourListe($client_id ? '' : $clientRecherche) as $c)
                                            <button type="button" wire:click="selectionnerClient({{ $c->id }})" @click="open = false"
                                                    class="block w-full px-3 py-2 text-left text-sm hover:bg-emerald-50 {{ $client_id === $c->id ? 'bg-emerald-50 font-medium text-emerald-700' : 'text-slate-700' }}">
                                                {{ $c->nom }}
                                            </button>
                                        @empty
                                            <p class="px-3 py-2 text-sm text-slate-400">Aucun client — sera créé à la validation.</p>
                                        @endforelse
                                    </div>
                                </div>
                                @error('client_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror

                                @php $clientsCorrespondants = $client_id ? collect() : $this->clientsCorrespondants(); @endphp
                                @if ($client_id)
                                    <p class="text-xs mt-1 text-emerald-600">Client selectionne.</p>
                                @elseif ($clientsCorrespondants->count() > 1)
                                    <p class="text-xs mt-1 text-amber-600">{{ $clientsCorrespondants->count() }} clients correspondent, précisez le nom.</p>
                                @elseif (trim($clientRecherche) !== '')
                                    <p class="text-xs mt-1 text-slate-400">"{{ $clientRecherche }}" sera enregistré comme nouveau client à la validation.</p>
                                @endif
                            </div>
                        </div>

                        {{-- Caisse rapide : douchette code-barres (elle tape le code puis Entree) ou camera du telephone. --}}
                        <div x-data="scanCaisse" @scan-resultat.window="resultat($event.detail)"
                             class="rounded-xl border-2 border-dashed border-emerald-300 bg-gradient-to-r from-emerald-50 to-sky-50 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <div class="flex items-center gap-2 text-sm font-semibold text-emerald-800">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 font-mono text-xs text-white">▮▯▮</span>
                                    Scanner un article
                                </div>
                                <input type="text" x-ref="champ" wire:model="codeScan" wire:keydown.enter.prevent="scanner"
                                       placeholder="Passez la douchette ou tapez le code puis Entrée" autocomplete="off" autofocus
                                       class="input-field flex-1 font-mono">
                                <button type="button" x-show="cameraDisponible" x-cloak @click="ouvrirCamera()" class="btn-ghost whitespace-nowrap">📷 Caméra</button>
                            </div>
                            <p x-show="message" x-cloak x-transition x-text="message" :class="succes ? 'text-emerald-700' : 'text-rose-600'" class="mt-2 text-sm font-medium"></p>
                            @error('codeScan') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror

                            <div x-show="cameraOuverte" x-cloak class="fixed inset-0 z-[70] flex flex-col items-center justify-center gap-4 bg-slate-950/90 p-4" @keydown.escape.window="fermerCamera()">
                                <p class="text-sm text-white">Placez le code-barres dans le cadre</p>
                                <div class="relative w-full max-w-md overflow-hidden rounded-2xl ring-4 ring-emerald-400">
                                    <video x-ref="video" playsinline muted class="w-full"></video>
                                    <div class="pointer-events-none absolute inset-x-8 top-1/2 h-0.5 -translate-y-1/2 animate-pulse bg-rose-500"></div>
                                </div>
                                <button type="button" @click="fermerCamera()" class="btn-ghost bg-white">Fermer</button>
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200">
                            <table class="min-w-full text-sm">
                                <thead class="table-head">
                                    <tr>
                                        <th class="px-3 py-2.5 text-left">Article</th>
                                        <th class="px-3 py-2.5 text-right w-24">Quantité</th>
                                        <th class="px-3 py-2.5 text-right w-28">Prix unitaire</th>
                                        <th class="px-3 py-2.5 text-right w-28">Sous-total</th>
                                        <th class="px-3 py-2.5 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($lignes as $index => $ligne)
                                        @php
                                            $articleChoisi = $articlesDisponibles->firstWhere('id', (int) ($ligne['article_id'] ?? 0));
                                            $quantiteDemandee = (int) ($ligne['quantite'] ?: 0);
                                            $stockDepasse = $articleChoisi && $quantiteDemandee > $articleChoisi->stock_disponible;
                                        @endphp
                                        <tr class="{{ $stockDepasse ? 'bg-rose-50/60' : '' }}">
                                            <td class="px-3 py-2 align-top">
                                                <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                                                    <input type="text" wire:model.live.debounce.300ms="lignes.{{ $index }}.recherche"
                                                           placeholder="Rechercher ou saisir un article..." autocomplete="off"
                                                           x-on:focus="open = true; $el.select()"
                                                           x-on:input="open = true"
                                                           x-on:keydown.escape="open = false"
                                                           class="w-full input-field">

                                                    <div x-show="open" x-cloak
                                                         class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg">
                                                        {{-- Meme logique que pour le client : un article deja confirme ne
                                                             doit pas limiter la liste a lui-meme quand on rouvre le menu. --}}
                                                        @forelse ($this->articlesPourListe(($ligne['article_id'] ?? null) ? '' : ($ligne['recherche'] ?? '')) as $a)
                                                            <button type="button" wire:click="selectionnerArticle({{ $index }}, {{ $a->id }})" @click="open = false"
                                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm hover:bg-emerald-50 {{ ($ligne['article_id'] ?? null) == $a->id ? 'bg-emerald-50 font-medium text-emerald-700' : 'text-slate-700' }}">
                                                                <x-article-vignette :article="$a" taille="h-8 w-8" />
                                                                <span class="min-w-0 flex-1"><span class="font-medium">{{ $a->code }}</span> — {{ $a->designation }}
                                                                <span class="text-xs text-slate-400">(stock : {{ $a->stock_disponible }})</span></span>
                                                            </button>
                                                        @empty
                                                            <p class="px-3 py-2 text-sm text-slate-400">Aucun article — sera créé à la validation.</p>
                                                        @endforelse
                                                    </div>
                                                </div>
                                                @php $articlesCorrespondants = $articleChoisi ? collect() : $this->articlesCorrespondants($ligne['recherche'] ?? ''); @endphp
                                                @if ($articleChoisi)
                                                    <p class="text-xs mt-1 {{ $stockDepasse ? 'text-rose-600 font-medium' : 'text-slate-400' }}">
                                                        Stock disponible : {{ $articleChoisi->stock_disponible }}
                                                    </p>
                                                @elseif ($articlesCorrespondants->count() > 1)
                                                    <p class="text-xs mt-1 text-amber-600">{{ $articlesCorrespondants->count() }} articles correspondent, précisez.</p>
                                                @elseif (trim($ligne['recherche'] ?? '') !== '')
                                                    <p class="text-xs mt-1 text-slate-400">"{{ $ligne['recherche'] }}" sera enregistré comme nouvel article à la validation.</p>
                                                @endif
                                                @error("lignes.{$index}.article_id") <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input type="number" min="1" wire:model.live.debounce.400ms="lignes.{{ $index }}.quantite" class="w-full input-field text-right">
                                                @error("lignes.{$index}.quantite") <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="lignes.{{ $index }}.prix_unitaire" class="w-full input-field text-right">
                                                @error("lignes.{$index}.prix_unitaire") <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                                            </td>
                                            <td class="px-3 py-2 align-top text-right font-medium text-slate-700">
                                                {{ number_format(($ligne['quantite'] ?: 0) * ($ligne['prix_unitaire'] ?: 0), 0, ',', ' ') }}
                                            </td>
                                            <td class="px-3 py-2 align-top text-right">
                                                @if (count($lignes) > 1)
                                                    <button type="button" wire:click="retirerLigne({{ $index }})" class="text-slate-400 hover:text-rose-600" title="Retirer cette ligne">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <button type="button" wire:click="ajouterLigne" class="btn-ghost">
                                + Ajouter un article
                            </button>

                            @php
                                $sousTotalPanier = collect($lignes)->sum(fn ($l) => ($l['quantite'] ?: 0) * ($l['prix_unitaire'] ?: 0));
                                $remisePourcentageNum = (float) ($remisePourcentage ?: 0);
                                $remiseMontantPanier = round($sousTotalPanier * $remisePourcentageNum / 100, 2);
                                $totalNetPanier = max(0, $sousTotalPanier - $remiseMontantPanier);
                            @endphp
                            <div class="w-full sm:w-72 space-y-2">
                                <div class="flex items-center justify-between text-sm text-slate-600">
                                    <span>Sous-total</span>
                                    <span>{{ number_format($sousTotalPanier, 0, ',', ' ') }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <label class="text-sm text-slate-600" for="remise-panier">Remise (%)</label>
                                    <div class="flex items-center gap-1 w-32">
                                        <input id="remise-panier" type="number" step="0.01" min="0" max="100"
                                               wire:model.live.debounce.400ms="remisePourcentage"
                                               class="w-full input-field text-right">
                                        <span class="text-sm text-slate-500">%</span>
                                    </div>
                                </div>
                                @error('remisePourcentage') <p class="text-xs text-rose-600 text-right">{{ $message }}</p> @enderror
                                @if ($remisePourcentageNum > 0)
                                    <div class="flex items-center justify-between text-xs text-slate-500">
                                        <span>Montant remise</span>
                                        <span>-{{ number_format($remiseMontantPanier, 0, ',', ' ') }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-2.5">
                                    <span class="text-sm font-medium text-emerald-700">Total du panier</span>
                                    <span class="text-lg font-bold text-emerald-800">{{ number_format($totalNetPanier, 0, ',', ' ') }} {{ config('app.currency') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-lg border border-slate-200 p-4">
                            <div>
                                <label class="label-field">Mode de paiement</label>
                                <select wire:model.live="modePaiement" class="mt-1 w-full input-field">
                                    @foreach ($this::MODES_PAIEMENT as $mode)
                                        <option value="{{ $mode }}">{{ $mode }}</option>
                                    @endforeach
                                </select>
                                @error('modePaiement') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                            </div>

                            @if ($modePaiement === 'Especes')
                                @php
                                    $montantRemisNum = $montantRemis !== '' ? (float) $montantRemis : null;
                                    $monnaiePanier = $montantRemisNum !== null ? round($montantRemisNum - $totalNetPanier, 2) : null;
                                @endphp
                                <div>
                                    <label class="label-field">Montant remis par le client</label>
                                    <input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="montantRemis"
                                           placeholder="Optionnel" class="mt-1 w-full input-field">
                                    @error('montantRemis') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror

                                    @if ($monnaiePanier !== null && $monnaiePanier > 0)
                                        <div class="mt-2 flex items-center justify-between rounded-lg bg-amber-50 px-3 py-2">
                                            <span class="text-sm font-medium text-amber-700">Monnaie à rendre</span>
                                            <span class="text-sm font-bold text-amber-800">{{ number_format($monnaiePanier, 0, ',', ' ') }} {{ config('app.currency') }}</span>
                                        </div>
                                        <label class="mt-2 flex items-start gap-2 text-xs text-slate-600">
                                            <input type="checkbox" wire:model="monnaieNonRendue" class="mt-0.5">
                                            <span>Le client n'a pas reçu sa monnaie (pas de petites coupures) — à lui donner plus tard. Sera ajouté à « Monnaie à rendre ».</span>
                                        </label>
                                    @elseif ($monnaiePanier !== null && $monnaiePanier === 0.0)
                                        <p class="mt-2 text-xs text-emerald-600">Compte juste, aucune monnaie à rendre.</p>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="flex gap-3 pt-2">
                            <x-btn-submit>{{ $editingId ? 'Enregistrer les modifications' : 'Enregistrer' }}</x-btn-submit>
                            <button type="button" wire:click="annuler" class="btn-ghost">Annuler</button>
                        </div>
                    </form>
                </div>
            @else
                <div class="card p-5">
                    <p class="text-sm text-slate-400 text-center py-16">
                        Cliquez sur « + Nouvelle vente » pour ouvrir le panier ici.
                    </p>
                </div>
            @endif
        </div>

        <div class="lg:col-span-1">
            <div class="card p-5 lg:sticky lg:top-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <x-nav-icon name="receipt" class="h-4.5 w-4.5" />
                        </span>
                        <h2 class="font-semibold text-slate-800">Reçu</h2>
                    </div>
                    <button type="button" x-show="recuUrl" x-cloak
                            @click="$refs.recuFrame.contentWindow.print()" class="btn-primary text-sm py-1.5">
                        Imprimer
                    </button>
                </div>

                <template x-if="!recuUrl">
                    <p class="text-sm text-slate-400 text-center py-16">
                        Le reçu d'une vente apparaît ici après validation du panier.
                    </p>
                </template>
                <template x-if="recuUrl">
                    <iframe x-ref="recuFrame" :src="recuUrl" class="w-full rounded-lg border border-slate-200" style="height: 70vh;"></iframe>
                </template>
            </div>
        </div>
    </div>
</div>
