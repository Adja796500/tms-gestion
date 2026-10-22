<?php

namespace App\Livewire;

use App\Models\ArchiveMensuelle;
use App\Models\Facture;
use App\Support\Finances;
use App\Support\Sauvegarde;
use Carbon\CarbonImmutable;
use Livewire\Component;

class Archives extends Component
{
    /** Mois a archiver, au format AAAA-MM. */
    public string $moisAArchiver = '';

    public function mount(): void
    {
        $this->moisAArchiver = now()->subMonthNoOverflow()->format('Y-m');
    }

    /**
     * Macro ArchiverLeMois : cloture d'un mois, avec ses chiffres figes (nombre de
     * factures, chiffre d'affaires, depenses, benefice). Re-archiver un mois deja
     * cloture rafraichit ses chiffres au lieu de creer un doublon.
     */
    public function archiver(Finances $finances): void
    {
        abort_unless(auth()->user()->peutModifier('ARCHIVES'), 403);

        $this->validate(['moisAArchiver' => ['required', 'regex:/^\d{4}-\d{2}$/']]);

        $debut = CarbonImmutable::createFromFormat('Y-m-d', $this->moisAArchiver.'-01')->startOfDay();
        $fin = $debut->endOfMonth();
        $resultat = $finances->resultat($debut, $fin);

        ArchiveMensuelle::updateOrCreate(
            ['entreprise_id' => auth()->user()->entreprise_id, 'mois' => $debut->toDateString()],
            [
                'nb_factures' => Facture::where('type', Facture::TYPE_DEFINITIVE)
                    ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
                    ->count(),
                'chiffre_affaires' => $resultat['entrees'],
                'depenses' => $resultat['depenses'],
                'benefice' => $resultat['benefice'],
                'utilisateur_id' => auth()->id(),
            ]
        );

        session()->flash('status', 'Mois archivé avec succès : '.Finances::libelleMois($debut).'.');
    }

    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()->peutModifier('ARCHIVES'), 403);

        ArchiveMensuelle::findOrFail($id)->delete();
        session()->flash('status', 'Archive supprimée.');
    }

    public function render(Finances $finances)
    {
        return view('livewire.archives', [
            'archives' => ArchiveMensuelle::with('utilisateur')->latest('mois')->get(),
            'moisDisponibles' => $finances->mois()->reverse(),
            'sauvegardesAuto' => auth()->user()->peutModifier('ARCHIVES') ? Sauvegarde::liste(auth()->user()->entreprise_id) : collect(),
        ])->layout('layouts.app', ['title' => 'Archives / Historique']);
    }
}
