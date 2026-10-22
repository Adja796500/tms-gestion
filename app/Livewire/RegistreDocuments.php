<?php

namespace App\Livewire;

use App\Models\Facture;
use App\Models\PaiementClient;
use App\Models\Recu;
use App\Support\EnvoiDocument;
use App\Support\NumeroGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class RegistreDocuments extends Component
{
    use WithPagination;

    /** Module dont depend la modification du statut, par type de document. */
    private const MODULE_PAR_TYPE = [
        Facture::TYPE_PROFORMA => 'FACTURE_PROFORMA',
        Facture::TYPE_DEFINITIVE => 'FACTURE_DEFINITIVE',
        Facture::TYPE_BORDEREAU => 'BORDEREAU_LIVRAISON',
        Facture::TYPE_BON_COMMANDE => 'BON_COMMANDE',
    ];

    public string $search = '';
    public string $filtreType = '';
    public string $filtreCreance = '';

    // Encaissement d'une facture depuis le registre (fenetre "Encaisser").
    public ?int $encaissementFactureId = null;
    public string $encaissementMontant = '';
    public string $encaissementMode = 'Especes';
    public string $encaissementDate = '';
    public bool $genererRecu = true;

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filtreType', 'filtreCreance'], true)) {
            $this->resetPage();
        }
    }

    public function changerStatut(int $id, string $statut): void
    {
        $facture = Facture::findOrFail($id);

        abort_unless(auth()->user()->peutModifier(self::MODULE_PAR_TYPE[$facture->type] ?? 'REGISTRE_DOCUMENTS'), 403);
        abort_unless(in_array($statut, Facture::STATUTS, true), 422);

        $facture->update(['statut' => $statut]);
        session()->flash('status', "Statut du document {$facture->numero} mis à jour.");
    }

    public function envoyerParEmail(int $id): void
    {
        $document = Facture::findOrFail($id);
        abort_unless(auth()->user()->peutModifier(self::MODULE_PAR_TYPE[$document->type] ?? 'REGISTRE_DOCUMENTS'), 403);

        try {
            $email = EnvoiDocument::envoyer($document);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('email', "L'email n'a pas pu partir : vérifiez la configuration email du serveur.");

            return;
        }

        session()->flash('status', $email
            ? "Document {$document->numero} envoyé par email à {$email}."
            : "Aucune adresse email valide pour {$document->nomPartenaire()} : complétez sa fiche.");
    }

    public function ouvrirEncaissement(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('PAIEMENTS_CLIENTS'), 403);

        $facture = Facture::where('type', Facture::TYPE_DEFINITIVE)->withSum('paiements', 'montant')->findOrFail($id);

        $this->encaissementFactureId = $facture->id;
        $this->encaissementMontant = (string) max(0, (float) $facture->montant_total - (float) $facture->paiements_sum_montant);
        $this->encaissementMode = 'Especes';
        $this->encaissementDate = now()->toDateString();
        $this->genererRecu = auth()->user()->peutModifier('RECU');
        $this->resetErrorBag();
    }

    public function fermerEncaissement(): void
    {
        $this->encaissementFactureId = null;
    }

    /**
     * Enregistre le paiement rattache a la facture (le reste a payer se met a jour),
     * passe la facture en "Payée" si elle est soldee et genere le recu si demande.
     */
    public function encaisser(): void
    {
        abort_unless(auth()->user()->peutModifier('PAIEMENTS_CLIENTS'), 403);

        $this->validate([
            'encaissementMontant' => 'required|numeric|min:1',
            'encaissementMode' => 'required|in:Especes,Cheque,Virement,Mobile Money,Orange Money,Moov Money',
            'encaissementDate' => 'required|date',
        ], ['encaissementMontant.min' => 'Le montant doit être supérieur à 0.']);

        $facture = Facture::where('type', Facture::TYPE_DEFINITIVE)->withSum('paiements', 'montant')->findOrFail($this->encaissementFactureId);
        $reste = (float) $facture->montant_total - (float) $facture->paiements_sum_montant;

        if ((float) $this->encaissementMontant > $reste + 0.01) {
            $this->addError('encaissementMontant', 'Le montant dépasse le reste à payer ('.number_format($reste, 0, ',', ' ').').');

            return;
        }

        $recuGenere = $this->genererRecu && auth()->user()->peutModifier('RECU');
        $entrepriseId = auth()->user()->entreprise_id;

        DB::transaction(function () use ($facture, $reste, $recuGenere, $entrepriseId) {
            $paiement = PaiementClient::create([
                'numero' => NumeroGenerator::suivant('paiements_clients', 'PAI', $entrepriseId),
                'date' => $this->encaissementDate,
                'client_id' => $facture->client_id,
                'facture_id' => $facture->id,
                'montant' => $this->encaissementMontant,
                'mode_paiement' => $this->encaissementMode,
                'observations' => 'Règlement facture '.$facture->numero,
            ]);

            if ((float) $this->encaissementMontant >= $reste - 0.01) {
                $facture->update(['statut' => 'Payée']);
            }

            if ($recuGenere) {
                Recu::create([
                    'numero' => NumeroGenerator::suivant('recus', 'REC', $entrepriseId),
                    'paiement_client_id' => $paiement->id,
                    'date' => $paiement->date,
                    'montant' => $paiement->montant,
                ]);
            }
        });

        $this->encaissementFactureId = null;
        session()->flash('status', 'Paiement enregistré pour la facture '.$facture->numero.($recuGenere ? ' (reçu généré).' : '.'));
    }

    /**
     * Lien WhatsApp pre-rempli vers le client (numero a 8 chiffres = Burkina, +226).
     */
    private static function lienWhatsApp(?string $telephone, string $message): ?string
    {
        $chiffres = preg_replace('/\D/', '', (string) $telephone);

        if ($chiffres === '') {
            return null;
        }

        if (strlen($chiffres) === 8) {
            $chiffres = '226'.$chiffres;
        }

        return 'https://wa.me/'.$chiffres.'?text='.rawurlencode($message);
    }

    /**
     * Une ligne par document emis, comme REGISTRE_DOCUMENTS : les factures (et
     * recus) ont en plus le montant paye, le reste a payer et le statut de creance.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function lignes(): Collection
    {
        $nomEntreprise = auth()->user()->entreprise?->nom ?? config('app.name');

        $factures = Facture::query()
            ->with(['client', 'fournisseur', 'lignes'])
            ->withSum('paiements', 'montant')
            ->get()
            ->map(function (Facture $f) use ($nomEntreprise) {
                $estFacture = $f->type === Facture::TYPE_DEFINITIVE;
                $paye = $estFacture ? (float) ($f->paiements_sum_montant ?? 0) : null;
                $reste = $estFacture ? (float) $f->montant_total - $paye : null;
                $montant = number_format((float) $f->montant_total, 0, ',', ' ').' '.config('app.currency');
                $message = "Bonjour {$f->nomPartenaire()},\n{$nomEntreprise} vous transmet {$f->libelleType()} N° {$f->numero} du {$f->date->format('d/m/Y')}"
                    .($f->type === Facture::TYPE_BORDEREAU ? '.' : " d'un montant de {$montant}.")
                    .($estFacture && $reste > 0 ? "\nReste à payer : ".number_format($reste, 0, ',', ' ').' '.config('app.currency').'.' : '')
                    ."\nMerci de votre confiance.";
                $telephone = $f->type === Facture::TYPE_BON_COMMANDE ? $f->fournisseur?->telephone : $f->client?->telephone;

                return [
                    'id' => $f->id,
                    'est_facture' => true,
                    'numero' => $f->numero,
                    'date' => $f->date,
                    'type' => $f->type,
                    'libelle_type' => $f->libelleType(),
                    'partenaire' => $f->nomPartenaire(),
                    'designation' => $f->designationResumee(),
                    'montant' => (float) $f->montant_total,
                    'statut' => $f->statut,
                    'paye' => $paye,
                    'reste' => $reste,
                    'statut_creance' => $estFacture ? self::statutCreance($paye, $reste) : null,
                    'encaissable' => $estFacture && $reste > 0 && $f->statut !== 'Annulée',
                    'email' => EnvoiDocument::adresse($f),
                    'envoyee_le' => $f->envoyee_le,
                    'url_pdf' => route('documents.pdf', $f),
                    'whatsapp' => self::lienWhatsApp($telephone, $message),
                    'modifiable' => auth()->user()->peutModifier(self::MODULE_PAR_TYPE[$f->type] ?? 'REGISTRE_DOCUMENTS'),
                    'url_impression' => in_array($f->type, [Facture::TYPE_BORDEREAU, Facture::TYPE_BON_COMMANDE], true)
                        ? route('documents.imprimer', $f)
                        : route('factures.imprimer', $f),
                ];
            });

        $recus = Recu::query()
            ->with('paiementClient.client')
            ->get()
            ->map(fn (Recu $r) => [
                'id' => $r->id,
                'est_facture' => false,
                'numero' => $r->numero,
                'date' => $r->date,
                'type' => 'recu',
                'libelle_type' => 'Reçu',
                'partenaire' => $r->paiementClient->client->nom ?? '—',
                'designation' => $r->paiementClient->observations ?? 'Paiement '.($r->paiementClient->numero ?? ''),
                'montant' => (float) $r->montant,
                'statut' => 'Payée',
                'paye' => (float) $r->montant,
                'reste' => 0.0,
                'statut_creance' => 'Payée',
                'encaissable' => false,
                'email' => null,
                'envoyee_le' => null,
                'url_pdf' => null,
                'whatsapp' => null,
                'modifiable' => false,
                'url_impression' => route('recus.imprimer', $r),
            ]);

        return $factures->concat($recus)
            ->sortByDesc(fn ($l) => $l['date']->format('Y-m-d').'-'.str_pad((string) $l['id'], 10, '0', STR_PAD_LEFT))
            ->values();
    }

    private static function statutCreance(float $paye, float $reste): string
    {
        if ($reste <= 0) {
            return 'Payée';
        }

        return $paye == 0.0 ? 'Impayée' : 'Partiellement payée';
    }

    public function render()
    {
        $toutes = $this->lignes();
        $texte = mb_strtolower(trim($this->search));

        $filtrees = $toutes
            ->when($this->filtreType !== '', fn ($c) => $c->where('type', $this->filtreType))
            ->when($this->filtreCreance !== '', fn ($c) => $c->where('statut_creance', $this->filtreCreance))
            ->when($texte !== '', fn ($c) => $c->filter(fn ($l) => str_contains(mb_strtolower($l['numero'].' '.$l['partenaire'].' '.$l['designation']), $texte)))
            ->values();

        $page = $this->getPage();
        $parPage = 20;
        $documents = new LengthAwarePaginator($filtrees->forPage($page, $parPage), $filtrees->count(), $parPage, $page);

        $impayees = $toutes->filter(fn ($l) => in_array($l['statut_creance'], ['Impayée', 'Partiellement payée'], true));

        return view('livewire.registre-documents', [
            'documents' => $documents,
            'nbDocuments' => $toutes->count(),
            'nbImpayes' => $impayees->count(),
            'resteAEncaisser' => $impayees->sum('reste'),
            'types' => Facture::LIBELLES_TYPE + ['recu' => 'Reçu'],
            'factureAEncaisser' => $this->encaissementFactureId ? Facture::with('client')->find($this->encaissementFactureId) : null,
        ])->layout('layouts.app', ['title' => 'Registre des documents']);
    }
}
