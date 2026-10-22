<?php

namespace App\Mail;

use App\Models\Facture;
use App\Models\ParametreEntreprise;
use App\Support\DocumentPdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Envoi d'un document commercial en PDF, ou relance d'une facture impayee.
 */
class DocumentMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Facture $document, public bool $relance = false)
    {
    }

    private function parametres(): ?ParametreEntreprise
    {
        return ParametreEntreprise::withoutGlobalScopes()->where('entreprise_id', $this->document->entreprise_id)->first();
    }

    public function envelope(): Envelope
    {
        $entreprise = $this->parametres()?->nom_entreprise ?: config('app.name');
        $sujet = $this->relance
            ? "Rappel : facture {$this->document->numero} en attente de règlement"
            : "{$this->document->libelleType()} {$this->document->numero}";

        return new Envelope(
            subject: $sujet.' — '.$entreprise,
            replyTo: $this->parametres()?->email ? [$this->parametres()->email] : [],
        );
    }

    public function content(): Content
    {
        $this->document->loadMissing(['client', 'fournisseur', 'paiements']);
        $paye = (float) $this->document->paiements->sum('montant');

        return new Content(view: 'emails.document', with: [
            'document' => $this->document,
            'relance' => $this->relance,
            'parametres' => $this->parametres(),
            'reste' => (float) $this->document->montant_total - $paye,
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => DocumentPdf::contenu($this->document), DocumentPdf::nomFichier($this->document))
                ->withMime('application/pdf'),
        ];
    }
}
