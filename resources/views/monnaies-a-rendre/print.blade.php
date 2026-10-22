<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Monnaie #{{ $monnaie->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1e293b; margin: 40px; }
        h1 { font-size: 20px; margin-bottom: 0; }
        .muted { color: #64748b; font-size: 12px; }
        .box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; margin-top: 24px; }
        .amount { font-size: 24px; font-weight: bold; margin-top: 16px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: bold; margin-top: 8px; }
        .badge-due { background: #fffbeb; color: #b45309; }
        .badge-reglee { background: #ecfdf5; color: #047857; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .btn-print { margin-top: 24px; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ config('app.name') }}</h1>
            <p class="muted">JUSTIFICATIF DE MONNAIE</p>
        </div>
        <div style="text-align: right;">
            <p class="muted">Date : {{ $monnaie->date->format('d/m/Y') }}</p>
            @if ($monnaie->vente)
                <p class="muted">Vente : {{ $monnaie->vente->numero }}</p>
            @endif
        </div>
    </div>

    <div class="box">
        <p><strong>Client :</strong> {{ $monnaie->client->nom ?? '—' }}</p>
        <p class="amount">{{ number_format($monnaie->montant, 0, ',', ' ') }} {{ config('app.currency') }}</p>

        @if ($monnaie->estReglee())
            <span class="badge badge-reglee">Monnaie rendue le {{ $monnaie->reglee_le->format('d/m/Y a H:i') }}</span>
            <p class="muted" style="margin-top: 16px;">
                Ce document atteste que le client a bien reçu la monnaie ci-dessus.
            </p>
        @else
            <span class="badge badge-due">Monnaie à rendre</span>
            <p class="muted" style="margin-top: 16px;">
                Ce document atteste que le magasin doit encore ce montant au client
                (monnaie non disponible au moment de la vente).
            </p>
        @endif
    </div>

    <button class="btn-print" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    @if (request('print'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
