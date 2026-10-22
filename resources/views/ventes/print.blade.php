<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $vente->numero }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1e293b; margin: 40px; }
        h1 { font-size: 20px; margin-bottom: 0; }
        .muted { color: #64748b; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px 6px; font-size: 13px; text-align: left; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 11px; color: #475569; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #1e293b; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .btn-print { margin-top: 24px; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ config('app.name') }}</h1>
            <p class="muted">REÇU DE VENTE</p>
        </div>
        <div class="text-right">
            <p><strong>N° {{ $vente->numero }}</strong></p>
            <p class="muted">Date : {{ $vente->date->format('d/m/Y') }}</p>
        </div>
    </div>

    <p><strong>Client :</strong> {{ $vente->client->nom ?? '—' }}<br>
        <span class="muted">{{ $vente->client->telephone }} {{ $vente->client->email }}</span><br>
        <span class="muted">{{ $vente->client->adresse }}</span>
    </p>

    <table>
        <thead>
            <tr>
                <th>Article</th>
                <th class="text-right">Quantité</th>
                <th class="text-right">Prix unitaire</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vente->lignes as $ligne)
                <tr>
                    <td>{{ $ligne->article->designation ?? 'Article supprime' }}</td>
                    <td class="text-right">{{ $ligne->quantite }}</td>
                    <td class="text-right">{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($ligne->total, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
            @if ($vente->remise > 0)
                <tr>
                    <td colspan="3" class="text-right">Sous-total</td>
                    <td class="text-right">{{ number_format($vente->total + $vente->remise, 0, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right">Remise ({{ rtrim(rtrim(number_format($vente->remise_pourcentage, 2, '.', ''), '0'), '.') }}%)</td>
                    <td class="text-right">-{{ number_format($vente->remise, 0, ',', ' ') }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" class="text-right">Montant total</td>
                <td class="text-right">{{ number_format($vente->total, 0, ',', ' ') }} {{ config('app.currency') }}</td>
            </tr>
        </tbody>
    </table>

    <p class="muted" style="margin-top: 12px;">Paye par : {{ $vente->mode_paiement ?? 'Especes' }}</p>

    @if ($vente->mode_paiement === 'Especes' && $vente->montant_remis !== null)
        @php
            $monnaie = round($vente->montant_remis - $vente->total, 2);
            $monnaieDue = $vente->monnaiesARendre->firstWhere('statut', 'due');
        @endphp
        <p class="muted">Montant remis : {{ number_format($vente->montant_remis, 0, ',', ' ') }} {{ config('app.currency') }}</p>
        @if ($monnaie > 0 && $monnaieDue)
            <p class="muted">Monnaie à rendre (non remise) : {{ number_format($monnaie, 0, ',', ' ') }} {{ config('app.currency') }}</p>
        @elseif ($monnaie > 0)
            <p class="muted">Monnaie rendue : {{ number_format($monnaie, 0, ',', ' ') }} {{ config('app.currency') }}</p>
        @endif
    @endif

    <button class="btn-print" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    @if (request('print'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
