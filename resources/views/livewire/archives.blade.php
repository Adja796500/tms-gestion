@php($fcfa = fn ($v) => number_format($v, 0, ',', ' ').' '.config('app.currency'))
@php($peutModifier = auth()->user()->peutModifier('ARCHIVES'))
<div class="space-y-6">
    <p class="text-xs italic text-slate-500">Historique des clôtures mensuelles. Chaque archive fige les chiffres du mois et permet de télécharger ses mouvements (fichiers CSV lisibles dans Excel).</p>

    @if ($peutModifier)
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="card p-5">
                <h2 class="font-semibold text-slate-800">Archiver le mois</h2>
                <p class="mt-1 text-xs text-slate-500">Enregistre la clôture du mois choisi (nombre de factures, chiffre d'affaires, dépenses, bénéfice).</p>
                <form wire:submit="archiver" class="mt-4 flex flex-wrap items-end gap-3">
                    <div>
                        <label class="label-field">Mois à archiver</label>
                        <select wire:model="moisAArchiver" class="mt-1 input-field w-56">
                            @foreach ($moisDisponibles as $m)
                                <option value="{{ $m->format('Y-m') }}">{{ \App\Support\Finances::libelleMois($m) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-btn-submit target="archiver">Archiver le mois</x-btn-submit>
                </form>
            </div>

            <div class="card p-5">
                <h2 class="font-semibold text-slate-800">Sauvegarde complète</h2>
                <p class="mt-1 text-xs text-slate-500">Télécharge toutes les données de l'entreprise (clients, articles, ventes, achats, journal, documents...) dans un fichier ZIP. Conservez-le sur une clé USB ou un dossier cloud.</p>
                <a href="{{ route('sauvegarde.telecharger') }}" class="btn-primary mt-4 inline-flex">Télécharger la sauvegarde</a>

                <div class="mt-5 border-t border-slate-100 pt-4">
                    <p class="text-sm font-medium text-slate-800">Sauvegardes automatiques (chaque nuit à 2 h)</p>
                    <p class="text-xs text-slate-500">Les {{ \App\Support\Sauvegarde::A_CONSERVER }} plus récentes sont conservées sur le serveur.</p>
                    @forelse ($sauvegardesAuto->take(7) as $s)
                        <div class="mt-2 flex items-center justify-between gap-3 text-sm">
                            <span class="text-slate-600">{{ $s['date']->format('d/m/Y à H:i') }} <span class="text-xs text-slate-400">({{ number_format($s['taille'] / 1024, 0, ',', ' ') }} Ko)</span></span>
                            <a href="{{ route('sauvegarde.automatique', $s['nom']) }}" class="link-edit">Télécharger</a>
                        </div>
                    @empty
                        <p class="mt-2 text-xs text-amber-700">Aucune sauvegarde automatique pour l'instant : la première sera créée cette nuit (si le planificateur du serveur est activé).</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <div class="card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">Date d'archivage</th>
                    <th class="px-4 py-3 text-left">Mois concerné</th>
                    <th class="px-4 py-3 text-left">Année</th>
                    <th class="px-4 py-3 text-right">Nombre de factures</th>
                    <th class="px-4 py-3 text-right">Chiffre d'affaires</th>
                    <th class="px-4 py-3 text-right">Dépenses</th>
                    <th class="px-4 py-3 text-right">Bénéfice</th>
                    <th class="px-4 py-3 text-left">Archivé par</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($archives as $archive)
                    <tr wire:key="archive-{{ $archive->id }}">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $archive->updated_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 font-medium">{{ \App\Support\Finances::libelleMois($archive->mois) }}</td>
                        <td class="px-4 py-3">{{ $archive->mois->format('Y') }}</td>
                        <td class="px-4 py-3 text-right">{{ $archive->nb_factures }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">{{ $fcfa($archive->chiffre_affaires) }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">{{ $fcfa($archive->depenses) }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap {{ $archive->benefice < 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $fcfa($archive->benefice) }}</td>
                        <td class="px-4 py-3">{{ $archive->utilisateur->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('archives.exporter', $archive->mois->format('Y-m')) }}" class="link-edit mr-3">Télécharger</a>
                            @if ($peutModifier)
                                <x-action-delete call="supprimer({{ $archive->id }})" label="cette archive" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="9" message="Aucun mois archivé." />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
