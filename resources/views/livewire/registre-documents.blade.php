@php($fcfa = fn ($v) => number_format($v, 0, ',', ' ').' '.config('app.currency'))
<div class="space-y-6">
    <p class="text-xs italic text-slate-500">Historique de toutes les factures proforma, factures, reçus, bordereaux et bons de commande émis. Le montant payé vient des paiements clients rattachés à la facture.</p>

    <div class="cascade grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-kpi-card icon="document" color="slate" label="Documents émis" value="{{ $nbDocuments }}" />
        <x-kpi-card icon="alert" color="amber" label="Factures non soldées" value="{{ $nbImpayes }}" />
        <x-kpi-card icon="clock" color="rose" label="Reste à encaisser" value="{{ $fcfa($resteAEncaisser) }}" />
    </div>

    @error('email')
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
    @enderror

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Rechercher (N°, client, désignation)..." class="w-full sm:w-80 input-field">
        <select wire:model.live="filtreType" class="w-full sm:w-52 input-field">
            <option value="">Tous les types</option>
            @foreach ($types as $code => $libelle)
                <option value="{{ $code }}">{{ $libelle }}</option>
            @endforeach
        </select>
        <select wire:model.live="filtreCreance" class="w-full sm:w-56 input-field">
            <option value="">Tous les statuts de créance</option>
            <option value="Payée">Payée</option>
            <option value="Partiellement payée">Partiellement payée</option>
            <option value="Impayée">Impayée</option>
        </select>
    </div>

    <div class="card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-3 py-3 text-left">N°</th>
                    <th class="px-3 py-3 text-left">Date</th>
                    <th class="px-3 py-3 text-left">Type</th>
                    <th class="px-3 py-3 text-left">Client / Fournisseur</th>
                    <th class="px-3 py-3 text-left">Désignation résumée</th>
                    <th class="px-3 py-3 text-right">Montant total</th>
                    <th class="px-3 py-3 text-left">Statut</th>
                    <th class="px-3 py-3 text-right">Montant payé</th>
                    <th class="px-3 py-3 text-right">Reste à payer</th>
                    <th class="px-3 py-3 text-left">Statut créance</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($documents as $doc)
                    <tr wire:key="reg-{{ $doc['type'] }}-{{ $doc['id'] }}">
                        <td class="px-3 py-2.5 font-medium whitespace-nowrap">{{ $doc['numero'] }}</td>
                        <td class="px-3 py-2.5 whitespace-nowrap">{{ $doc['date']->format('d/m/Y') }}</td>
                        <td class="px-3 py-2.5 whitespace-nowrap">{{ $doc['libelle_type'] }}</td>
                        <td class="px-3 py-2.5">{{ $doc['partenaire'] }}</td>
                        <td class="px-3 py-2.5 max-w-[13rem] text-xs text-slate-600">{{ $doc['designation'] }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $doc['type'] === 'bordereau' ? '—' : $fcfa($doc['montant']) }}</td>
                        <td class="px-3 py-2.5">
                            @if ($doc['est_facture'] && $doc['modifiable'])
                                <select wire:change="changerStatut({{ $doc['id'] }}, $event.target.value)" class="input-field min-w-[7.5rem] px-2 py-1 text-xs">
                                    @foreach (\App\Models\Facture::STATUTS as $s)
                                        <option value="{{ $s }}" @selected($s === $doc['statut'])>{{ $s }}</option>
                                    @endforeach
                                </select>
                            @else
                                <span class="badge-neutral">{{ $doc['statut'] }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $doc['paye'] === null ? '' : $fcfa($doc['paye']) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $doc['reste'] === null ? '' : $fcfa($doc['reste']) }}</td>
                        <td class="px-3 py-2.5 whitespace-nowrap">
                            @if ($doc['statut_creance'])
                                <span class="{{ match ($doc['statut_creance']) { 'Payée' => 'badge-success', 'Impayée' => 'badge-danger', default => 'badge-warning' } }}">{{ $doc['statut_creance'] }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            @if ($doc['encaissable'] && auth()->user()->peutModifier('PAIEMENTS_CLIENTS'))
                                <button type="button" wire:click="ouvrirEncaissement({{ $doc['id'] }})" class="mr-3 font-medium text-emerald-700 hover:underline">Encaisser</button>
                            @endif
                            @if ($doc['est_facture'] && $doc['email'] && $doc['modifiable'])
                                <button type="button" wire:click="envoyerParEmail({{ $doc['id'] }})" wire:loading.attr="disabled" wire:target="envoyerParEmail({{ $doc['id'] }})"
                                        class="mr-3 font-medium text-sky-700 hover:underline disabled:opacity-50" title="{{ $doc['envoyee_le'] ? 'Déjà envoyé le '.$doc['envoyee_le']->format('d/m/Y H:i') : 'Envoyer le PDF à '.$doc['email'] }}">
                                    <span wire:loading.remove wire:target="envoyerParEmail({{ $doc['id'] }})">Email{{ $doc['envoyee_le'] ? ' ✓' : '' }}</span>
                                    <span wire:loading wire:target="envoyerParEmail({{ $doc['id'] }})">Envoi...</span>
                                </button>
                            @endif
                            @if ($doc['url_pdf'])
                                <a href="{{ $doc['url_pdf'] }}" target="_blank" class="mr-3 font-medium text-rose-700 hover:underline">PDF</a>
                            @endif
                            @if ($doc['whatsapp'])
                                <a href="{{ $doc['whatsapp'] }}" target="_blank" rel="noopener" class="mr-3 font-medium text-green-700 hover:underline" title="Envoyer au client par WhatsApp">WhatsApp</a>
                            @endif
                            <a href="{{ $doc['url_impression'] }}" target="_blank" class="link-edit">Imprimer</a>
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="11" message="Aucun document." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $documents->links() }}</div>
    </div>

    @if ($factureAEncaisser)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 p-4" wire:keydown.escape.window="fermerEncaissement">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" @click.outside="$wire.fermerEncaissement()">
                <h3 class="font-semibold text-slate-900">Encaisser la facture {{ $factureAEncaisser->numero }}</h3>
                <p class="mb-4 text-sm text-slate-500">{{ $factureAEncaisser->client->nom ?? '' }} — total {{ $fcfa($factureAEncaisser->montant_total) }}</p>
                <form wire:submit="encaisser" class="space-y-4">
                    <div>
                        <label class="label-field">Montant encaissé</label>
                        <input type="number" min="1" step="1" wire:model="encaissementMontant" class="mt-1 w-full input-field" autofocus>
                        @error('encaissementMontant') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label-field">Mode de paiement</label>
                            <select wire:model="encaissementMode" class="mt-1 w-full input-field">
                                @foreach (['Especes' => 'Espèces', 'Orange Money' => 'Orange Money', 'Moov Money' => 'Moov Money', 'Cheque' => 'Chèque', 'Virement' => 'Virement'] as $code => $libelle)
                                    <option value="{{ $code }}">{{ $libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label-field">Date</label>
                            <input type="date" wire:model="encaissementDate" class="mt-1 w-full input-field">
                        </div>
                    </div>
                    @if (auth()->user()->peutModifier('RECU'))
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="genererRecu" class="h-4 w-4 rounded border-slate-300 text-brand-600">
                            Générer le reçu automatiquement
                        </label>
                    @endif
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="fermerEncaissement" class="btn-ghost">Annuler</button>
                        <x-btn-submit target="encaisser">Enregistrer le paiement</x-btn-submit>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
