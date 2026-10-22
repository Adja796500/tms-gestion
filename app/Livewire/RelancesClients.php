<?php

namespace App\Livewire;

use App\Models\Facture;
use App\Models\ParametreEntreprise;
use App\Support\EnvoiDocument;
use App\Support\Relances;
use Livewire\Component;

class RelancesClients extends Component
{
    // Reglages (Configuration entreprise), modifiables ici pour plus de commodite.
    public bool $relances_actives = false;
    public bool $envoi_auto_factures = false;
    public string $relance_delai_jours = '7';
    public string $relance_intervalle_jours = '7';

    public function mount(): void
    {
        $parametres = $this->parametres();
        $this->relances_actives = (bool) $parametres->relances_actives;
        $this->envoi_auto_factures = (bool) $parametres->envoi_auto_factures;
        $this->relance_delai_jours = (string) $parametres->relance_delai_jours;
        $this->relance_intervalle_jours = (string) $parametres->relance_intervalle_jours;
    }

    private function parametres(): ParametreEntreprise
    {
        return ParametreEntreprise::firstOrCreate(['entreprise_id' => auth()->user()->entreprise_id]);
    }

    public function enregistrerReglages(): void
    {
        abort_unless(auth()->user()->peutModifier('CONFIGURATION_ENTREPRISE'), 403);

        $data = $this->validate([
            'relances_actives' => 'boolean',
            'envoi_auto_factures' => 'boolean',
            'relance_delai_jours' => 'required|integer|min:1|max:365',
            'relance_intervalle_jours' => 'required|integer|min:1|max:365',
        ]);

        $this->parametres()->update($data);
        session()->flash('status', 'Réglages des envois automatiques enregistrés.');
    }

    public function relancer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('CREANCES_CLIENTS'), 403);

        $facture = Facture::where('type', Facture::TYPE_DEFINITIVE)->findOrFail($id);

        try {
            $email = EnvoiDocument::envoyer($facture, relance: true);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('envoi', "La relance n'a pas pu partir : vérifiez la configuration email du serveur.");

            return;
        }

        session()->flash('status', $email
            ? "Relance de la facture {$facture->numero} envoyée à {$email}."
            : "Pas d'adresse email pour ce client : utilisez WhatsApp ou complétez sa fiche.");
    }

    /**
     * Lien WhatsApp pre-rempli avec un message de relance poli.
     */
    public static function lienWhatsApp(Facture $facture, string $entreprise): ?string
    {
        $chiffres = preg_replace('/\D/', '', (string) $facture->client?->telephone);

        if ($chiffres === '') {
            return null;
        }

        if (strlen($chiffres) === 8) {
            $chiffres = '226'.$chiffres;
        }

        $message = "Bonjour {$facture->client->nom},\n{$entreprise} vous rappelle que la facture N° {$facture->numero} du {$facture->date->format('d/m/Y')} "
            .'reste à régler : '.number_format($facture->reste, 0, ',', ' ').' '.config('app.currency').".\n"
            ."Si le paiement a déjà été fait, merci de ne pas tenir compte de ce message.\nMerci de votre confiance.";

        return 'https://wa.me/'.$chiffres.'?text='.rawurlencode($message);
    }

    public function render()
    {
        $impayees = Relances::impayees(auth()->user()->entreprise_id);

        return view('livewire.relances-clients', [
            'impayees' => $impayees,
            'totalDu' => $impayees->sum('reste'),
            'nbClients' => $impayees->pluck('client_id')->unique()->count(),
            'plusDe30' => $impayees->where('jours_retard', '>', 30)->sum('reste'),
            'entreprise' => auth()->user()->entreprise?->nom ?? config('app.name'),
            'emailConfigure' => ! in_array(config('mail.default'), ['log', 'array'], true),
        ])->layout('layouts.app', ['title' => 'Relances clients']);
    }
}
