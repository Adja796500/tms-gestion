<div class="space-y-6" x-data="{ recuUrl: null }"
     x-on:monnaie-reglee.window="recuUrl = '{{ url('/monnaies-a-rendre') }}/' + $event.detail.monnaieId + '/imprimer?print=1'">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Rechercher par client..."
               class="w-full sm:w-80 input-field">
        <div class="flex items-center gap-3 rounded-lg bg-amber-50 px-4 py-2.5">
            <span class="text-sm font-medium text-amber-700">Total dû à rendre</span>
            <span class="text-lg font-bold text-amber-800">{{ number_format($totalDu, 0, ',', ' ') }} {{ config('app.currency') }}</span>
        </div>
    </div>

    <div class="lg:grid lg:grid-cols-3 lg:gap-6 lg:items-start space-y-6 lg:space-y-0">
        <div class="lg:col-span-2">
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="table-head">
                            <tr>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-left">Client</th>
                                <th class="px-4 py-3 text-left">Vente</th>
                                <th class="px-4 py-3 text-right">Montant</th>
                                <th class="px-4 py-3 text-left">Statut</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($entrees as $entree)
                                <tr>
                                    <td class="px-4 py-3">{{ $entree->date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">{{ $entree->client->nom ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $entree->vente->numero ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right font-medium">{{ number_format($entree->montant, 0, ',', ' ') }}</td>
                                    <td class="px-4 py-3">
                                        @if ($entree->estReglee())
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Rendue</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">A rendre</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <button type="button" @click="recuUrl = '{{ url('/monnaies-a-rendre/'.$entree->id.'/imprimer') }}'"
                                                class="link-edit mr-3" title="Voir / imprimer le justificatif">
                                            Aperçu
                                        </button>
                                        @if (! $entree->estReglee() && auth()->user()->peutModifier(\App\Livewire\Ventes::MODULES_CAISSE))
                                            <button type="button" wire:click="marquerReglee({{ $entree->id }})" class="link-edit">
                                                Marquer comme rendue
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-etat-vide colspan="6" message="Aucune monnaie en attente." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $entrees->links() }}</div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="card p-5 lg:sticky lg:top-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <x-nav-icon name="receipt" class="h-4.5 w-4.5" />
                        </span>
                        <h2 class="font-semibold text-slate-800">Justificatif</h2>
                    </div>
                    <button type="button" x-show="recuUrl" x-cloak
                            @click="$refs.recuFrame.contentWindow.print()" class="btn-primary text-sm py-1.5">
                        Imprimer
                    </button>
                </div>

                <template x-if="!recuUrl">
                    <p class="text-sm text-slate-400 text-center py-16">
                        Le justificatif apparaît ici en cliquant sur « Aperçu », ou automatiquement dès qu'une monnaie est marquée comme rendue.
                    </p>
                </template>
                <template x-if="recuUrl">
                    <iframe x-ref="recuFrame" :src="recuUrl" class="w-full rounded-lg border border-slate-200" style="height: 60vh;"></iframe>
                </template>
            </div>
        </div>
    </div>
</div>
