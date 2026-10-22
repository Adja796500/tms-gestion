<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $recu->numero }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1e293b; margin: 40px; }
        h1 { font-size: 20px; margin-bottom: 0; }
        .muted { color: #64748b; font-size: 12px; }
        .box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; margin-top: 24px; }
        .amount { font-size: 24px; font-weight: bold; margin-top: 16px; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }}</h1>
    <p class="muted">REÇU DE PAIEMENT N° {{ $recu->numero }}</p>

    <div class="box">
        <p><strong>Date :</strong> {{ $recu->date->format('d/m/Y') }}</p>
        <p><strong>Client :</strong> {{ $recu->paiementClient->client->nom ?? '—' }}</p>
        <p><strong>Mode de paiement :</strong> {{ $recu->paiementClient->mode_paiement ?? '—' }}</p>
        <p class="amount">{{ number_format($recu->montant, 0, ',', ' ') }} {{ config('app.currency') }}</p>
    </div>

    <button class="btn-print" onclick="window.print()" style="margin-top:24px;">Imprimer / Enregistrer en PDF</button>
</body>
</html>
