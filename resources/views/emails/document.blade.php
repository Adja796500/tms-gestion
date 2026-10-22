@php
    $entreprise = $parametres?->nom_entreprise ?: config('app.name');
    $fcfa = fn ($v) => number_format((float) $v, 0, ',', ' ').' '.config('app.currency');
    $retard = $relance ? (int) $document->date->diffInDays(now()) : 0;
@endphp
<!DOCTYPE html>
<html lang="fr">
<body style="margin:0; padding:0; background:#f1f5f9; font-family: Arial, Helvetica, sans-serif; color:#1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; padding:24px 0;">
        <tr><td align="center">
            <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden; max-width:94%;">
                <tr><td style="background:linear-gradient(90deg,#1f3864,#4338ca); background-color:#1f3864; padding:20px 28px; color:#ffffff;">
                    <div style="font-size:18px; font-weight:bold;">{{ $entreprise }}</div>
                    @if ($parametres?->telephone)<div style="font-size:12px; opacity:.8;">Tél : {{ $parametres->telephone }}</div>@endif
                </td></tr>
                <tr><td style="padding:28px;">
                    <p style="margin:0 0 14px;">Bonjour {{ $document->nomPartenaire() }},</p>
                    @if ($relance)
                        <p style="margin:0 0 14px;">Sauf erreur de notre part, la facture <strong>{{ $document->numero }}</strong> du {{ $document->date->format('d/m/Y') }} reste en attente de règlement depuis {{ $retard }} jour(s).</p>
                    @else
                        <p style="margin:0 0 14px;">Veuillez trouver ci-joint votre {{ mb_strtolower($document->libelleType()) }} <strong>{{ $document->numero }}</strong> du {{ $document->date->format('d/m/Y') }}.</p>
                    @endif

                    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; margin:6px 0 18px;">
                        <tr><td style="padding:12px 16px; font-size:13px; color:#64748b;">Document</td><td align="right" style="padding:12px 16px; font-weight:bold;">{{ $document->libelleType() }} {{ $document->numero }}</td></tr>
                        @if ($document->type !== 'bordereau')
                            <tr><td style="padding:0 16px 12px; font-size:13px; color:#64748b;">Montant total</td><td align="right" style="padding:0 16px 12px; font-weight:bold;">{{ $fcfa($document->montant_total) }}</td></tr>
                        @endif
                        @if ($document->type === 'definitive' && $reste > 0)
                            <tr><td style="padding:0 16px 12px; font-size:13px; color:#b91c1c;">Reste à payer</td><td align="right" style="padding:0 16px 12px; font-weight:bold; color:#b91c1c;">{{ $fcfa($reste) }}</td></tr>
                        @endif
                    </table>

                    @if ($relance)
                        <p style="margin:0 0 14px;">Merci de bien vouloir procéder au règlement dans les meilleurs délais. Si le paiement a déjà été effectué, veuillez ne pas tenir compte de ce message.</p>
                    @endif
                    <p style="margin:0 0 4px;">Le document est joint à ce message au format PDF.</p>
                    <p style="margin:18px 0 0;">Cordialement,<br><strong>{{ $parametres?->signataire ?: $entreprise }}</strong></p>
                </td></tr>
                <tr><td style="padding:14px 28px; background:#f8fafc; font-size:11px; color:#94a3b8;">
                    {{ $entreprise }}@if ($parametres?->adresse) — {{ $parametres->adresse }}@endif @if ($parametres?->email) — {{ $parametres->email }}@endif
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
