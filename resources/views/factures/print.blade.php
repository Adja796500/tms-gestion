<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $facture->numero }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1e293b; margin: 40px; font-size: 13px; }
        .header { display: flex; align-items: center; gap: 20px; border-bottom: 3px solid #1d4ed8; padding-bottom: 16px; }
        .logo { height: 70px; width: 70px; object-fit: contain; flex-shrink: 0; }
        .entreprise-info { text-align: center; flex: 1; }
        .entreprise-nom { font-size: 26px; font-weight: bold; color: #1e293b; margin: 0; }
        .entreprise-activites { font-size: 11px; color: #475569; margin: 4px 0; }
        .entreprise-contact { font-size: 12px; font-weight: bold; margin: 4px 0 0; }
        .entreprise-contact .tel { color: #1d4ed8; }
        .entreprise-contact .email { color: #ea580c; }
        .date-ligne { text-align: right; margin-top: 16px; font-size: 12px; }
        .titre { text-align: center; font-size: 18px; font-weight: bold; color: #1d4ed8; text-decoration: underline; margin: 20px 0; }
        .doit, .objet { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #1d4ed8; color: #fff; text-transform: uppercase; font-size: 11px; padding: 10px 8px; text-align: left; }
        th.text-right, td.text-right { text-align: right; }
        td { padding: 10px 8px; font-size: 13px; border-bottom: 1px solid #e2e8f0; }
        tbody tr:nth-child(even) td { background: #eff6ff; }
        .total-row td { background: #1d4ed8 !important; color: #fff; font-weight: bold; border-bottom: none; }
        .montant-lettres { margin-top: 20px; font-size: 12px; }
        .signature { margin-top: 60px; text-align: right; }
        .signature p { margin: 2px 0; }
        .footer { margin-top: 60px; border-top: 1px solid #cbd5e1; padding-top: 8px; display: flex; justify-content: space-between; font-size: 10px; color: #64748b; }
        .btn-print { margin-top: 24px; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        @if ($parametres?->logoUrl())
            <img src="{{ $parametres->logoUrl() }}" alt="Logo" class="logo">
        @endif
        <div class="entreprise-info">
            <p class="entreprise-nom">{{ $parametres?->nom_entreprise ?: config('app.name') }}</p>
            @if ($parametres?->activites_texte)
                <p class="entreprise-activites">{{ $parametres->activites_texte }}</p>
            @endif
            @if ($parametres?->telephone || $parametres?->email)
                <p class="entreprise-contact">
                    @if ($parametres?->telephone)
                        <span class="tel">Tél : {{ $parametres->telephone }}</span>
                    @endif
                    @if ($parametres?->email)
                        <br><span class="email">{{ $parametres->email }}</span>
                    @endif
                </p>
            @endif
        </div>
    </div>

    <p class="date-ligne">{{ $parametres?->ville ? $parametres->ville.', ' : '' }}le {{ $facture->date->format('d/m/Y') }}</p>

    <p class="titre">{{ $facture->type === 'proforma' ? 'FACTURE PROFORMA' : 'FACTURE DEFINITIVE' }} N°{{ $facture->numero }}</p>

    <p class="doit"><strong>Doit :</strong> {{ $facture->client->nom ?? '—' }}</p>
    @if ($facture->objet)
        <p class="objet"><strong>Objet :</strong> {{ $facture->objet }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="text-right">Quantité</th>
                <th class="text-right">Prix unitaire<br>{{ config('app.currency') }}</th>
                <th class="text-right">Prix total<br>{{ config('app.currency') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($facture->lignes as $ligne)
                <tr>
                    <td>{{ $ligne->designation }}</td>
                    <td class="text-right">{{ $ligne->quantite }}</td>
                    <td class="text-right">{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($ligne->total, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3">TOTAL GÉNÉRAL</td>
                <td class="text-right">{{ number_format($facture->montant_total, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <p class="montant-lettres">
        Arrêté le présent reçu à la somme de :
        {{ \App\Support\NombreEnLettres::enFrancsCfa((float) $facture->montant_total) }}
        ({{ number_format($facture->montant_total, 0, ',', ' ') }} {{ config('app.currency') }})
    </p>

    <div class="signature">
        <p>Le Responsable</p>
        <br><br>
        @if ($parametres?->signataire)
            <p>{{ $parametres->signataire }}</p>
        @endif
    </div>

    <div class="footer">
        <div>
            <strong>{{ $parametres?->nom_entreprise ?: config('app.name') }}</strong><br>
            @if ($parametres?->regime_fiscal)
                Régime fiscal : {{ $parametres->regime_fiscal }}<br>
            @endif
            @if ($parametres?->division_fiscale)
                Division fiscale : {{ $parametres->division_fiscale }}<br>
            @endif
            @if ($parametres?->adresse)
                {{ $parametres->adresse }}
            @endif
        </div>
        <div style="text-align: right;">
            @if ($parametres?->rccm)
                RCCM : {{ $parametres->rccm }}<br>
            @endif
            @if ($parametres?->ifu)
                IFU : {{ $parametres->ifu }}
            @endif
        </div>
    </div>

    <button class="btn-print" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    @if (request('print'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
