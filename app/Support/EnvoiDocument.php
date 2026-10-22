<?php

namespace App\Support;

use App\Mail\DocumentMail;
use App\Models\ActivityLog;
use App\Models\Facture;
use App\Models\ParametreEntreprise;
use Illuminate\Support\Facades\Mail;

/**
 * Envoi d'un document par email (manuel, automatique a la creation, ou relance).
 * Retourne l'adresse utilisee, ou null si le client/fournisseur n'a pas d'email.
 */
class EnvoiDocument
{
    public static function adresse(Facture $document): ?string
    {
        $document->loadMissing(['client', 'fournisseur']);
        $email = $document->type === Facture::TYPE_BON_COMMANDE ? $document->fournisseur?->email : $document->client?->email;

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public static function envoyer(Facture $document, bool $relance = false): ?string
    {
        $email = self::adresse($document);

        if (! $email) {
            return null;
        }

        Mail::to($email)->send(new DocumentMail($document, $relance));

        // Requete ciblee plutot que save() : le document peut porter des attributs
        // calcules (reste, jours de retard) qui ne sont pas des colonnes.
        $suivi = $relance
            ? ['derniere_relance_le' => now(), 'nb_relances' => (int) $document->nb_relances + 1]
            : ['envoyee_le' => now()];
        Facture::withoutGlobalScopes()->whereKey($document->getKey())->update($suivi);
        $document->setRawAttributes(array_merge($document->getAttributes(), $suivi), true);

        if (auth()->check()) {
            ActivityLog::enregistrer($relance ? 'relance' : 'envoi_email', 'Facture', $document->id, null, ['destinataire' => $email], $document->numero);
        }

        return $email;
    }

    /**
     * Envoi automatique juste apres la creation d'un document, si l'entreprise l'a
     * active et si le client a un email. Une panne d'email ne bloque jamais la
     * creation du document : on le signale seulement.
     *
     * @return string|null message a afficher, ou null si rien n'a ete tente
     */
    public static function apresCreation(Facture $document): ?string
    {
        $actif = ParametreEntreprise::withoutGlobalScopes()->where('entreprise_id', $document->entreprise_id)->value('envoi_auto_factures');

        if (! $actif || ! self::adresse($document)) {
            return null;
        }

        try {
            return 'Envoyé automatiquement par email à '.self::envoyer($document).'.';
        } catch (\Throwable $e) {
            report($e);

            return "L'envoi automatique par email a échoué (vérifiez la configuration email).";
        }
    }
}
