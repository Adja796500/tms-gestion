@php
    use App\Models\Facture;
    $estBC = $document->type === Facture::TYPE_BON_COMMANDE;
    $estBL = $document->type === Facture::TYPE_BORDEREAU;
    $partenaire = $estBC ? $document->fournisseur : $document->client;
    $titre = match ($document->type) {
        Facture::TYPE_PROFORMA => 'FACTURE PROFORMA',
        Facture::TYPE_DEFINITIVE => 'FACTURE',
        Facture::TYPE_BORDEREAU => 'BORDEREAU DE LIVRAISON',
        default => 'BON DE COMMANDE',
    };
    $nomEntreprise = $parametres?->nom_entreprise ?: config('app.name');
    $fcfa = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $paye = (float) $document->paiements->sum('montant');
    $reste = (float) $document->montant_total - $paye;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $document->numero }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        .entete td { vertical-align: middle; }
        .nom { font-size: 20px; font-weight: bold; color: #1f3864; }
        .activites { font-size: 9px; color: #475569; }
        .contact { font-size: 10px; color: #1d4ed8; font-weight: bold; }
        .filet { border-bottom: 3px solid #1d4ed8; margin: 10px 0 14px; }
        .titre { text-align: center; font-size: 16px; font-weight: bold; color: #1d4ed8; margin: 6px 0 14px; }
        .bloc td { padding: 2px 0; }
        .lignes { margin-top: 12px; }
        .lignes th { background: #1d4ed8; color: #fff; font-size: 9px; text-transform: uppercase; padding: 7px 6px; text-align: left; }
        .lignes td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; }
        .lignes tr:nth-child(even) td { background: #eff6ff; }
        .droite { text-align: right; }
        .total td { background: #1d4ed8 !important; color: #fff; font-weight: bold; }
        .lettres { margin-top: 12px; font-size: 10px; }
        .etat { margin-top: 10px; padding: 8px 10px; border-radius: 4px; font-size: 10px; }
        .etat-ok { background: #dcfce7; color: #166534; }
        .etat-du { background: #fee2e2; color: #991b1b; }
        .signature { margin-top: 40px; }
        .pied { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #cbd5e1; padding-top: 5px; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>
    <table class="entete">
        <tr>
            @if ($logo)
                <td style="width: 80px;"><img src="{{ $logo }}" style="height: 64px; max-width: 76px;"></td>
            @endif
            <td style="text-align: center;">
                <div class="nom">{{ $nomEntreprise }}</div>
                @if ($parametres?->activites_texte)
                    <div class="activites">{{ $parametres->activites_texte }}</div>
                @endif
                <div class="contact">
                    @if ($parametres?->telephone) Tél : {{ $parametres->telephone }} @endif
                    @if ($parametres?->email) &nbsp;•&nbsp; {{ $parametres->email }} @endif
                </div>
            </td>
        </tr>
    </table>
    <div class="filet"></div>

    <p style="text-align: right; margin: 0;">{{ $parametres?->ville ? $parametres->ville.', ' : '' }}le {{ $document->date->format('d/m/Y') }}</p>
    <div class="titre">{{ $titre }} N° {{ $document->numero }}</div>

    <table class="bloc">
        <tr><td style="width: 130px;"><strong>{{ $estBC ? 'Fournisseur' : 'Doit (Client)' }}</strong></td><td>{{ $partenaire->nom ?? '—' }}</td></tr>
        @if ($partenaire?->telephone)<tr><td><strong>Téléphone</strong></td><td>{{ $partenaire->telephone }}</td></tr>@endif
        @if ($partenaire?->adresse)<tr><td><strong>Adresse</strong></td><td>{{ $partenaire->adresse }}</td></tr>@endif
        @if ($document->objet)<tr><td><strong>Objet</strong></td><td>{{ $document->objet }}</td></tr>@endif
        @if ($document->factureLiee)<tr><td><strong>Facture liée</strong></td><td>{{ $document->factureLiee->numero }}</td></tr>@endif
    </table>

    <table class="lignes">
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="droite" style="width: 60px;">Quantité</th>
                @if ($estBL)
                    <th>Observations</th>
                @else
                    <th class="droite" style="width: 95px;">Prix unitaire</th>
                    <th class="droite" style="width: 100px;">Prix total</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($document->lignes as $ligne)
                <tr>
                    <td>{{ $ligne->designation }}</td>
                    <td class="droite">{{ $ligne->quantite }}</td>
                    @if ($estBL)
                        <td>{{ $ligne->observations }}</td>
                    @else
                        <td class="droite">{{ $fcfa($ligne->prix_unitaire) }}</td>
                        <td class="droite">{{ $fcfa($ligne->total) }}</td>
                    @endif
                </tr>
            @endforeach
            @unless ($estBL)
                @if ((float) $document->remise_pourcentage > 0)
                    <tr><td colspan="3">Remise ({{ (float) $document->remise_pourcentage }} %)</td><td class="droite">- {{ $fcfa($document->lignes->sum('total') - $document->montant_total) }}</td></tr>
                @endif
                <tr class="total"><td colspan="3">TOTAL GÉNÉRAL ({{ config('app.currency') }})</td><td class="droite">{{ $fcfa($document->montant_total) }}</td></tr>
            @endunless
        </tbody>
    </table>

    @unless ($estBL)
        <p class="lettres">
            Arrêté{{ $estBC ? ' le présent bon de commande' : 'e la présente facture' }} à la somme de :
            <strong>{{ \App\Support\NombreEnLettres::enFrancsCfa((float) $document->montant_total) }}</strong>
            ({{ $fcfa($document->montant_total) }} {{ config('app.currency') }})
        </p>
    @endunless

    @if ($document->type === Facture::TYPE_DEFINITIVE)
        @if ($reste <= 0)
            <div class="etat etat-ok">Facture entièrement réglée. Merci.</div>
        @else
            <div class="etat etat-du">Déjà réglé : {{ $fcfa($paye) }} {{ config('app.currency') }} — <strong>Reste à payer : {{ $fcfa($reste) }} {{ config('app.currency') }}</strong></div>
        @endif
    @endif

    @if ($estBL)
        <table class="signature"><tr><td>Livré par : ______________________</td><td style="text-align: right;">Reçu par (signature) : ______________________</td></tr></table>
    @else
        <table class="signature"><tr><td></td><td style="text-align: right; width: 220px;">Le Responsable<br><br><br>{{ $parametres?->signataire }}</td></tr></table>
    @endif

    <div class="pied">
        <table>
            <tr>
                <td>{{ $nomEntreprise }}@if ($parametres?->regime_fiscal) — Régime : {{ $parametres->regime_fiscal }}@endif @if ($parametres?->division_fiscale) — Division : {{ $parametres->division_fiscale }}@endif @if ($parametres?->adresse)<br>{{ $parametres->adresse }}@endif</td>
                <td style="text-align: right;">@if ($parametres?->rccm) RCCM : {{ $parametres->rccm }}@endif @if ($parametres?->ifu)<br>IFU : {{ $parametres->ifu }}@endif</td>
            </tr>
        </table>
    </div>
</body>
</html>
