<div class="space-y-6">
    <div class="flex justify-end">
        @if (auth()->user()->peutModifier($moduleCode))
            <button wire:click="creer" class="btn-primary">
                + Nouveau document
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">Nouveau document — {{ $titrePage }}</h2>
            <form wire:submit="enregistrer" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label-field">Date</label>
                        <input type="date" wire:model="date" class="mt-1 w-full input-field">
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
                        @if (! $client_id)
                            @if ($clientsCorrespondants->count() > 1)
                                <p class="text-xs mt-1 text-amber-600">{{ $clientsCorrespondants->count() }} clients correspondent, précisez le nom.</p>
                            @elseif (trim($clientRecherche) !== '')
                                <p class="text-xs mt-1 text-slate-400">"{{ $clientRecherche }}" sera enregistré comme nouveau client à la validation.</p>
                            @endif
                        @endif
                    </div>

                    @if ($client_id)
                        @php $infos = $this->clientInfos(); @endphp
                        @if ($infos)
                            <div class="sm:col-span-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                                <p class="text-xs font-medium text-emerald-700 mb-2">Informations du client — remplies automatiquement</p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="label-field">Téléphone</label>
                                        <input type="text" value="{{ $infos['client']->telephone ?: '—' }}" readonly
                                               class="mt-1 w-full input-field bg-white/70 text-slate-600 cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label class="label-field">Email</label>
                                        <input type="text" value="{{ $infos['client']->email ?: '—' }}" readonly
                                               class="mt-1 w-full input-field bg-white/70 text-slate-600 cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label class="label-field">Adresse</label>
                                        <input type="text" value="{{ $infos['client']->adresse ?: '—' }}" readonly
                                               class="mt-1 w-full input-field bg-white/70 text-slate-600 cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label class="label-field">Déjà payé</label>
                                        <input type="text" value="{{ number_format($infos['totalPaye'], 0, ',', ' ') }} {{ config('app.currency') }}" readonly
                                               class="mt-1 w-full input-field bg-white/70 text-emerald-700 font-medium cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label class="label-field">Reste à payer</label>
                                        <input type="text" value="{{ number_format($infos['resteAPayer'], 0, ',', ' ') }} {{ config('app.currency') }}" readonly
                                               class="mt-1 w-full input-field bg-white/70 font-medium cursor-not-allowed {{ $infos['resteAPayer'] > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="label-field">Dernières factures</label>
                                        <div class="mt-1 w-full input-field bg-white/70 text-slate-600 h-auto py-2 leading-relaxed">
                                            @forelse ($infos['dernieresFactures'] as $f)
                                                {{ $f->numero }} — {{ number_format($f->montant_total, 0, ',', ' ') }} {{ config('app.currency') }} ({{ $f->statut }})@if (! $loop->last) <br> @endif
                                            @empty
                                                —
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500 mt-2">Les lignes du document ci-dessous ont été pré-remplies avec ses derniers articles achetés — modifiables librement.</p>
                            </div>
                        @endif
                    @endif

                    <div class="sm:col-span-2">
                        <label class="label-field">Objet</label>
                        <input type="text" wire:model="objet" placeholder="Ex. : Impression des flyers" class="mt-1 w-full input-field">
                        @error('objet') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <p class="label-field">Lignes du document</p>
                    @foreach ($lignes as $index => $ligne)
                        <div class="grid grid-cols-1 sm:grid-cols-8 gap-2 items-start">
                            <div class="sm:col-span-4">
                                <input type="text" placeholder="Désignation" wire:model="lignes.{{ $index }}.designation"
                                       class="w-full input-field">
                                @error("lignes.$index.designation") <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <input type="number" placeholder="Quantité" wire:model="lignes.{{ $index }}.quantite"
                                       class="w-full input-field">
                            </div>
                            <div class="sm:col-span-1">
                                <input type="number" step="0.01" placeholder="Prix unit." wire:model="lignes.{{ $index }}.prix_unitaire"
                                       class="w-full input-field">
                            </div>
                            <div class="sm:col-span-1 flex items-center">
                                @if (count($lignes) > 1)
                                    <button type="button" wire:click="retirerLigne({{ $index }})" class="link-danger">Retirer</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    <button type="button" wire:click="ajouterLigne" class="link-edit">+ Ajouter une ligne</button>
                </div>

                <div class="flex gap-3 pt-2">
                    <x-btn-submit>Générer le document</x-btn-submit>
                    <button type="button" wire:click="annuler" class="btn-ghost">Annuler</button>
                </div>
            </form>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">N°</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-right">Montant total</th>
                    <th class="px-4 py-3 text-left">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($factures as $facture)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $facture->numero }}</td>
                        <td class="px-4 py-3">{{ $facture->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $facture->client->nom ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($facture->montant_total, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs">{{ $facture->statut }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('factures.imprimer', $facture) }}" target="_blank" class="link-edit mr-3">Imprimer</a>
                            @if ($facture->type === 'proforma' && $facture->statut !== 'Acceptée' && auth()->user()->peutModifier('FACTURE_DEFINITIVE'))
                                <button type="button" wire:click="convertirEnFacture({{ $facture->id }})" wire:confirm="Créer la facture définitive à partir de cette proforma ?" class="link-edit mr-3">→ Facturer</button>
                            @endif
                            @if ($facture->type === 'definitive' && auth()->user()->peutModifier('BORDEREAU_LIVRAISON'))
                                <a href="{{ route('bordereaux.index', ['facture' => $facture->id]) }}" class="link-edit mr-3">→ Bordereau</a>
                            @endif
                            @if (auth()->user()->peutModifier($moduleCode))
                                <x-action-delete call="supprimer({{ $facture->id }})" label="le document {{ $facture->numero }}" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="6" message="Aucun document." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $factures->links() }}</div>
    </div>
</div>
