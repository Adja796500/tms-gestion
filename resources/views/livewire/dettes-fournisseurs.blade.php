<div class="space-y-6">
    <p class="text-sm text-slate-500">Vue calculée automatiquement à partir des achats et des règlements enregistrés (équivalent de la feuille DETTES_FOURNISSEURS).</p>

    <div class="card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">Fournisseur</th>
                    <th class="px-4 py-3 text-right">Montant total achete</th>
                    <th class="px-4 py-3 text-right">Regle</th>
                    <th class="px-4 py-3 text-right">Solde restant</th>
                    <th class="px-4 py-3 text-left">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($fournisseurs as $fournisseur)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $fournisseur->nom }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($fournisseur->total_achete ?? 0, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($fournisseur->total_regle ?? 0, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ number_format($fournisseur->solde, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3">
                            @if ($fournisseur->solde <= 0)
                                <span class="badge-success">Solde</span>
                            @else
                                <span class="badge-warning">En attente</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-etat-vide colspan="5" message="Aucune donnee." />
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $fournisseurs->links() }}</div>
    </div>
</div>
