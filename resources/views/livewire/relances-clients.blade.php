@php
    $fcfa = fn ($v) => number_format($v, 0, ',', ' ').' '.config('app.currency');
    $peutRelancer = auth()->user()->peutModifier('CREANCES_CLIENTS');
@endphp
<div class="space-y-6">
    @unless ($emailConfigure)
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-nav-icon name="alert" class="mt-0.5 h-5 w-5 flex-shrink-0" />
            <div>
                <p class="font-semibold">Les emails ne partent pas encore réellement.</p>
                <p>Le serveur est en mode test (les messages sont seulement enregistrés dans le journal technique). Renseignez un compte email (SMTP) dans le fichier <code>.env</code> pour activer l'envoi — voir le Guide. Les liens WhatsApp fonctionnent déjà.</p>
            </div>
        </div>
    @endunless

    @error('envoi')
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
    @enderror

    <div class="cascade grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-kpi-card icon="clock" color="rose" label="Total en attente de paiement" value="{{ $fcfa($totalDu) }}" />
        <x-kpi-card icon="users" color="amber" label="Clients concernés" value="{{ $nbClients }}" />
        <x-kpi-card icon="alert" color="violet" label="Retard de plus de 30 jours" value="{{ $fcfa($plusDe30) }}" />
    </div>

    <div class="card overflow-x-auto">
        <div class="p-5 pb-3">
            <h2 class="font-semibold text-slate-800">Factures en retard de paiement</h2>
            <p class="text-xs text-slate-500">De la plus ancienne à la plus récente. La couleur indique l'ancienneté du retard.</p>
        </div>
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">Facture</th>
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-left">Retard</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Reste à payer</th>
                    <th class="px-4 py-3 text-left">Dernière relance</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($impayees as $facture)
                    @php
                        $teinte = $facture->jours_retard > 30 ? 'badge-danger' : ($facture->jours_retard > 7 ? 'badge-warning' : 'badge-neutral');
                        $whatsapp = \App\Livewire\RelancesClients::lienWhatsApp($facture, $entreprise);
                        $email = \App\Support\EnvoiDocument::adresse($facture);
                    @endphp
                    <tr wire:key="relance-{{ $facture->id }}">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $facture->numero }}</p>
                            <p class="text-xs text-slate-500">du {{ $facture->date->format('d/m/Y') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p>{{ $facture->client->nom ?? '—' }}</p>
                            <p class="text-xs text-slate-500">{{ $email ?: ($facture->client?->telephone ?: 'Aucun contact') }}</p>
                        </td>
                        <td class="px-4 py-3"><span class="{{ $teinte }}">{{ $facture->jours_retard }} jour(s)</span></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">{{ $fcfa($facture->montant_total) }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-rose-700">{{ $fcfa($facture->reste) }}</td>
                        <td class="px-4 py-3 text-xs text-slate-600">
                            @if ($facture->derniere_relance_le)
                                {{ $facture->derniere_relance_le->format('d/m/Y') }} <span class="text-slate-400">({{ $facture->nb_relances }} relance(s))</span>
                            @else
                                <span class="text-slate-400">Jamais relancée</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if ($peutRelancer && $email)
                                <button type="button" wire:click="relancer({{ $facture->id }})" wire:loading.attr="disabled" wire:target="relancer({{ $facture->id }})"
                                        class="mr-3 font-medium text-sky-700 hover:underline disabled:opacity-50">Relancer par email</button>
                            @endif
                            @if ($whatsapp)
                                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="mr-3 font-medium text-green-700 hover:underline">WhatsApp</a>
                            @endif
                            <a href="{{ route('documents.pdf', $facture) }}" target="_blank" class="font-medium text-rose-700 hover:underline">PDF</a>
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="7" message="Aucune facture en retard : tous vos clients sont à jour." />
                @endforelse
            </tbody>
        </table>
    </div>

    @if (auth()->user()->peutModifier('CONFIGURATION_ENTREPRISE'))
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800">Envois automatiques</h2>
            <p class="mb-4 text-xs text-slate-500">Les relances automatiques partent chaque matin à 8 h ; seuls les clients qui ont une adresse email sont relancés.</p>
            <form wire:submit="enregistrerReglages" class="space-y-4">
                <label class="flex items-start gap-3">
                    <input type="checkbox" wire:model="envoi_auto_factures" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600">
                    <span class="text-sm"><span class="font-medium text-slate-800">Envoyer automatiquement chaque nouveau document par email</span><br><span class="text-slate-500">Facture, proforma, bordereau ou bon de commande, dès sa création, si le client (ou le fournisseur) a un email.</span></span>
                </label>
                <label class="flex items-start gap-3">
                    <input type="checkbox" wire:model.live="relances_actives" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600">
                    <span class="text-sm"><span class="font-medium text-slate-800">Relancer automatiquement les clients en retard de paiement</span><br><span class="text-slate-500">Un email de rappel poli avec la facture en PDF et le reste à payer.</span></span>
                </label>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 {{ $relances_actives ? '' : 'opacity-50' }}">
                    <div>
                        <label class="label-field">Première relance après (jours de retard)</label>
                        <input type="number" min="1" max="365" wire:model="relance_delai_jours" class="mt-1 input-field">
                        @error('relance_delai_jours') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label-field">Puis relancer tous les (jours)</label>
                        <input type="number" min="1" max="365" wire:model="relance_intervalle_jours" class="mt-1 input-field">
                        @error('relance_intervalle_jours') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <x-btn-submit target="enregistrerReglages">Enregistrer les réglages</x-btn-submit>
            </form>
        </div>
    @endif
</div>
