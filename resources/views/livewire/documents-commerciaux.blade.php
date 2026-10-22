@php($peutModifier = auth()->user()->peutModifier($moduleCode))
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Rechercher (N°, {{ $estBonCommande ? 'fournisseur' : 'client' }})..."
               class="w-full sm:w-80 input-field">
        @if ($peutModifier)
            <button wire:click="creer" class="btn-primary">
                + {{ $estBonCommande ? 'Nouveau bon de commande' : 'Nouveau bordereau' }}
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-1">
                {{ $editingId ? 'Modifier' : 'Nouveau' }} {{ $estBonCommande ? 'bon de commande' : 'bordereau de livraison' }}
            </h2>
            <p class="text-xs text-slate-500 mb-4">Le numéro ({{ $estBonCommande ? 'BC' : 'BL' }}-ANNÉE-NNNNNN) est attribué automatiquement à l'enregistrement.</p>
            {{-- Totaux recalcules a chaque frappe dans le navigateur, comme les formules de la feuille BON_COMMANDE. --}}
            <form wire:submit="enregistrer" class="space-y-4"
                  x-data="{
                      ligne(i) { const l = $wire.lignes[i] || {}; return (parseInt(l.quantite) || 0) * (Number(l.prix_unitaire) || 0) },
                      get sousTotal() { return ($wire.lignes || []).reduce((s, l, i) => s + this.ligne(i), 0) },
                      get total() { return this.sousTotal * (1 - (Number($wire.remise_pourcentage) || 0) / 100) },
                      fcfa(v) { return Math.round(v).toLocaleString('fr-FR') },
                  }">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label-field">{{ $estBonCommande ? 'Fournisseur' : 'Client' }}</label>
                        <select wire:model="partenaire_id" class="mt-1 w-full input-field">
                            <option value="">-- Choisir --</option>
                            @foreach ($partenaires as $partenaire)
                                <option value="{{ $partenaire->id }}">{{ $partenaire->nom }}</option>
                            @endforeach
                        </select>
                        @error('partenaire_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label-field">Date</label>
                        <input type="date" wire:model="date" class="mt-1 w-full input-field">
                        @error('date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label-field">Statut</label>
                        <select wire:model="statut" class="mt-1 w-full input-field">
                            @foreach (\App\Models\Facture::STATUTS as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    @unless ($estBonCommande)
                        <div>
                            <label class="label-field">Référence facture liée (optionnel)</label>
                            <select wire:model.live="facture_liee_id" class="mt-1 w-full input-field">
                                <option value="">-- Aucune --</option>
                                @foreach ($facturesDisponibles as $facture)
                                    <option value="{{ $facture->id }}">{{ $facture->numero }} — {{ $facture->client->nom ?? '' }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-400 mt-1">Reprend le client et les articles de la facture.</p>
                        </div>
                    @endunless
                    <div class="sm:col-span-2">
                        <label class="label-field">Objet</label>
                        <input type="text" wire:model="objet" class="mt-1 w-full input-field">
                    </div>
                    @if ($estBonCommande)
                        <div>
                            <label class="label-field">Remise (%)</label>
                            <input type="number" step="0.01" min="0" max="100" wire:model="remise_pourcentage" class="mt-1 w-full input-field">
                            @error('remise_pourcentage') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>

                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="table-head">
                            <tr>
                                <th class="px-3 py-2 text-left">Désignation</th>
                                <th class="px-3 py-2 text-right w-28">Quantité</th>
                                @if ($estBonCommande)
                                    <th class="px-3 py-2 text-right w-40">Prix unitaire</th>
                                    <th class="px-3 py-2 text-right w-36">Prix total</th>
                                @else
                                    <th class="px-3 py-2 text-left">Observations</th>
                                @endif
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($lignes as $index => $ligne)
                                <tr wire:key="ligne-{{ $index }}">
                                    <td class="px-3 py-2">
                                        <input type="text" wire:model="lignes.{{ $index }}.designation" class="w-full input-field">
                                        @error("lignes.$index.designation") <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" min="1" wire:model="lignes.{{ $index }}.quantite" class="w-full input-field text-right">
                                    </td>
                                    @if ($estBonCommande)
                                        <td class="px-3 py-2">
                                            <input type="number" min="0" step="1" wire:model="lignes.{{ $index }}.prix_unitaire" class="w-full input-field text-right">
                                        </td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap" x-text="fcfa(ligne({{ $index }}))"></td>
                                    @else
                                        <td class="px-3 py-2">
                                            <input type="text" wire:model="lignes.{{ $index }}.observations" class="w-full input-field">
                                        </td>
                                    @endif
                                    <td class="px-3 py-2 text-right">
                                        <button type="button" wire:click="retirerLigne({{ $index }})" class="link-danger" title="Retirer la ligne">
                                            <x-nav-icon name="x" class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <button type="button" wire:click="ajouterLigne" class="btn-ghost">+ Ajouter une ligne</button>
                    @if ($estBonCommande)
                        <div class="text-right text-sm">
                            <p class="text-slate-500">Sous-total : <span x-text="fcfa(sousTotal)"></span> {{ config('app.currency') }}</p>
                            <p class="font-semibold text-slate-900">TOTAL GÉNÉRAL : <span x-text="fcfa(total)"></span> {{ config('app.currency') }}</p>
                        </div>
                    @endif
                </div>

                <div class="flex gap-3 pt-2">
                    <x-btn-submit>Enregistrer</x-btn-submit>
                    <button type="button" wire:click="annuler" class="btn-ghost">Annuler</button>
                </div>
            </form>
        </div>
    @endif

    <div class="card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">N°</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">{{ $estBonCommande ? 'Fournisseur' : 'Client' }}</th>
                    <th class="px-4 py-3 text-left">Désignation</th>
                    @if ($estBonCommande)
                        <th class="px-4 py-3 text-right">Montant</th>
                    @else
                        <th class="px-4 py-3 text-left">Facture liée</th>
                    @endif
                    <th class="px-4 py-3 text-left">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($documents as $document)
                    <tr wire:key="doc-{{ $document->id }}">
                        <td class="px-4 py-3 font-medium whitespace-nowrap">{{ $document->numero }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $document->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $document->nomPartenaire() }}</td>
                        <td class="px-4 py-3">{{ $document->designationResumee() }}</td>
                        @if ($estBonCommande)
                            <td class="px-4 py-3 text-right whitespace-nowrap">{{ number_format($document->montant_total, 0, ',', ' ') }} {{ config('app.currency') }}</td>
                        @else
                            <td class="px-4 py-3">{{ $document->factureLiee->numero ?? '—' }}</td>
                        @endif
                        <td class="px-4 py-3"><span class="badge-neutral">{{ $document->statut }}</span></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('documents.imprimer', $document) }}" target="_blank" class="link-edit mr-3">Imprimer</a>
                            @if ($peutModifier)
                                <x-action-edit call="modifier({{ $document->id }})" />
                                <x-action-delete call="supprimer({{ $document->id }})" label="le document {{ $document->numero }}" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="7" message="Aucun document." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $documents->links() }}</div>
    </div>
</div>
