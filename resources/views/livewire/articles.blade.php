<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Rechercher un article (code ou désignation)..."
               class="w-full sm:w-80 input-field">
        @if (auth()->user()->peutModifier('ARTICLES'))
            <button wire:click="creer" class="btn-primary">
                + Nouvel article
            </button>
        @endif
    </div>

    @if ($showForm)
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-4">{{ $editingId ? 'Modifier' : 'Nouvel' }} article</h2>
            <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-3 flex items-center gap-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                    @if ($photo && method_exists($photo, 'temporaryUrl'))
                        <img src="{{ $photo->temporaryUrl() }}" alt="Aperçu" class="h-20 w-20 rounded-xl object-cover ring-2 ring-brand-200">
                    @elseif ($photoActuelle && ! $retirerPhoto)
                        <img src="{{ $photoActuelle }}" alt="Photo actuelle" class="h-20 w-20 rounded-xl object-cover ring-2 ring-slate-200">
                    @else
                        <div class="flex h-20 w-20 items-center justify-center rounded-xl bg-white text-slate-300 ring-1 ring-slate-200">
                            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="5" width="18" height="14" rx="2" /><circle cx="9" cy="10" r="2" /><path d="m21 17-5-5-9 7" /></svg>
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <label class="label-field">Photo de l'article (optionnel)</label>
                        <input type="file" wire:model="photo" accept="image/*" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-brand-700">
                        <p wire:loading wire:target="photo" class="mt-1 text-xs text-brand-600">Envoi de la photo...</p>
                        @error('photo') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        @if ($photoActuelle && ! $photo)
                            <label class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600">
                                <input type="checkbox" wire:model.live="retirerPhoto" class="h-3.5 w-3.5 rounded border-slate-300"> Retirer la photo actuelle
                            </label>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="label-field">Code</label>
                    <input type="text" wire:model="code" class="mt-1 w-full input-field">
                    @error('code') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Code-barres (optionnel)</label>
                    <input type="text" wire:model="code_barre" placeholder="Scannez le produit ici" class="mt-1 w-full input-field font-mono" @keydown.enter.prevent>
                    <p class="text-xs text-slate-400 mt-1">Cliquez dans le champ puis passez la douchette sur le produit.</p>
                    @error('code_barre') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3">
                    <label class="label-field">Désignation</label>
                    <input type="text" wire:model="designation" class="mt-1 w-full input-field">
                    @error('designation') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Catégorie</label>
                    <input type="text" wire:model="categorie" class="mt-1 w-full input-field">
                </div>
                <div>
                    <label class="label-field">Unité</label>
                    <input type="text" wire:model="unite" class="mt-1 w-full input-field">
                </div>
                <div>
                    <label class="label-field">Prix d'achat ({{ config('app.currency') }})</label>
                    <input type="number" step="0.01" wire:model="prix_achat" class="mt-1 w-full input-field">
                </div>
                <div>
                    <label class="label-field">Prix de vente ({{ config('app.currency') }})</label>
                    <input type="number" step="0.01" wire:model="prix_vente" class="mt-1 w-full input-field">
                    @error('prix_vente') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Stock initial</label>
                    <input type="number" wire:model="stock_initial" class="mt-1 w-full input-field">
                </div>
                <div>
                    <label class="label-field">Stock minimum (seuil d'alerte)</label>
                    <input type="number" wire:model="stock_minimum" class="mt-1 w-full input-field">
                </div>

                @if (! $editingId)
                    <div class="sm:col-span-3 space-y-3 rounded-lg border border-slate-200 p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-700">Livraisons (optionnel)</h3>
                            <p class="text-xs text-slate-400">Qui a livré cet article, et quand — un même article peut avoir plusieurs fournisseurs.</p>
                        </div>

                        @foreach ($livraisons as $index => $livraison)
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-start">
                                <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                                    <input type="text" wire:model.live.debounce.300ms="livraisons.{{ $index }}.fournisseurRecherche"
                                           placeholder="Rechercher ou saisir un fournisseur..." autocomplete="off"
                                           x-on:focus="open = true; $el.select()"
                                           x-on:input="open = true"
                                           x-on:keydown.escape="open = false"
                                           class="w-full input-field">

                                    <div x-show="open" x-cloak
                                         class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg">
                                        @forelse ($this->fournisseursPourListe(($livraison['fournisseur_id'] ?? null) ? '' : ($livraison['fournisseurRecherche'] ?? '')) as $f)
                                            <button type="button" wire:click="selectionnerFournisseur({{ $index }}, {{ $f->id }})" @click="open = false"
                                                    class="block w-full px-3 py-2 text-left text-sm hover:bg-emerald-50 {{ ($livraison['fournisseur_id'] ?? null) == $f->id ? 'bg-emerald-50 font-medium text-emerald-700' : 'text-slate-700' }}">
                                                {{ $f->nom }}
                                            </button>
                                        @empty
                                            <p class="px-3 py-2 text-sm text-slate-400">Aucun fournisseur — sera créé à la validation.</p>
                                        @endforelse
                                    </div>
                                    @error("livraisons.{$index}.fournisseur_id") <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <input type="date" wire:model="livraisons.{{ $index }}.date" class="w-full input-field">
                                </div>
                                <div>
                                    <input type="number" min="0" wire:model="livraisons.{{ $index }}.quantite" placeholder="Quantité" class="w-full input-field">
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="number" step="0.01" min="0" wire:model="livraisons.{{ $index }}.prix_achat" placeholder="Prix d'achat" class="w-full input-field">
                                    @if (count($livraisons) > 1)
                                        <button type="button" wire:click="retirerLivraison({{ $index }})" class="text-slate-400 hover:text-rose-600 flex-shrink-0" title="Retirer cette livraison">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <button type="button" wire:click="ajouterLivraison" class="btn-ghost text-sm">
                            + Ajouter un fournisseur
                        </button>
                    </div>
                @endif

                <div class="sm:col-span-3 flex gap-3 pt-2">
                    <x-btn-submit>Enregistrer</x-btn-submit>
                    <button type="button" wire:click="annuler" class="btn-ghost">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">Code</th>
                    <th class="px-4 py-3 text-left">Désignation</th>
                    <th class="px-4 py-3 text-right">Prix vente</th>
                    <th class="px-4 py-3 text-right">Stock disponible</th>
                    <th class="px-4 py-3 text-left">Alerte</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($articles as $article)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $article->code }}@if ($article->code_barre)<br><span class="font-mono text-[11px] font-normal text-slate-400">▮▯▮ {{ $article->code_barre }}</span>@endif</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <x-article-vignette :article="$article" />
                                <span>{{ $article->designation }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">{{ number_format($article->prix_vente, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right">{{ $article->stock_disponible }}</td>
                        <td class="px-4 py-3">
                            @if ($article->estEnAlerte())
                                <span class="badge-danger">Stock bas</span>
                            @else
                                <span class="badge-success">OK</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if (auth()->user()->peutModifier('ARTICLES'))
                                <x-action-edit call="modifier({{ $article->id }})" />
                                <x-action-delete call="supprimer({{ $article->id }})" label="l'article {{ $article->code }}" />
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="6" message="Aucun article." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $articles->links() }}</div>
    </div>
</div>
