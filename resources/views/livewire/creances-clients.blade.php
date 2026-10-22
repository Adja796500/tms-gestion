<div class="space-y-6">
    <p class="text-sm text-slate-500">Vue calculée automatiquement à partir des ventes et des paiements enregistrés (équivalent de la feuille CREANCES_CLIENTS).</p>

    <div class="card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-right">Montant total facture</th>
                    <th class="px-4 py-3 text-right">Paye</th>
                    <th class="px-4 py-3 text-right">Reste à payer</th>
                    <th class="px-4 py-3 text-left">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($clients as $client)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $client->nom }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($client->total_facture ?? 0, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($client->total_paye ?? 0, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ number_format($client->reste, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3">
                            @if ($client->reste <= 0)
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
        <div class="p-4">{{ $clients->links() }}</div>
    </div>
</div>
