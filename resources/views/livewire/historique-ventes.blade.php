<div class="space-y-6" x-data="{ recuUrl: null }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Rechercher une vente (numéro ou client)..."
               class="w-full sm:w-80 input-field">
        @if (auth()->user()->peutModifier(\App\Livewire\Ventes::MODULES_CAISSE))
            <a href="{{ route('ventes.index') }}" class="btn-primary">
                + Nouvelle vente
            </a>
        @endif
    </div>

    <div class="lg:grid lg:grid-cols-3 lg:gap-6 lg:items-start space-y-6 lg:space-y-0">
        <div class="lg:col-span-2">
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="table-head">
                            <tr>
                                <th class="px-4 py-3 text-left">N°</th>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-left">Client</th>
                                <th class="px-4 py-3 text-left">Articles</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($ventes as $vente)
                                @php
                                    $designations = $vente->lignes->pluck('article.designation')->filter();
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $vente->numero }}</td>
                                    <td class="px-4 py-3">{{ $vente->date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">{{ $vente->client->nom ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-slate-700">{{ $designations->take(2)->implode(', ') }}</span>
                                        @if ($designations->count() > 2)
                                            <span class="ml-1 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">+{{ $designations->count() - 2 }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ number_format($vente->total, 0, ',', ' ') }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <button type="button" @click="recuUrl = '{{ url('/ventes/'.$vente->id.'/imprimer') }}'"
                                                class="link-edit mr-3 inline-flex items-center gap-1" title="Voir / imprimer le reçu">
                                            <x-nav-icon name="receipt" class="h-3.5 w-3.5" />
                                            <span>Aperçu</span>
                                        </button>
                                        @if (auth()->user()->peutModifier(\App\Livewire\Ventes::MODULES_CAISSE))
                                            <a href="{{ route('ventes.index', ['modifier' => $vente->id]) }}"
                                               class="link-edit mr-3 inline-flex items-center gap-1">
                                                <x-nav-icon name="pencil" class="h-3.5 w-3.5" />
                                                <span>Modifier</span>
                                            </a>
                                        @endif
                                        {{-- Supprimer une vente (elle disparait du chiffre d'affaires) reste reserve au droit VENTES. --}}
                                        @if (auth()->user()->peutModifier('VENTES'))
                                            <x-action-delete call="supprimer({{ $vente->id }})" label="la vente {{ $vente->numero }}" />
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-etat-vide colspan="6" message="Aucune vente." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $ventes->links() }}</div>
            </div>
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
                        Cliquez sur « Aperçu » sur une vente pour voir son reçu ici.
                    </p>
                </template>
                <template x-if="recuUrl">
                    <iframe x-ref="recuFrame" :src="recuUrl" class="w-full rounded-lg border border-slate-200" style="height: 70vh;"></iframe>
                </template>
            </div>
        </div>
    </div>
</div>
