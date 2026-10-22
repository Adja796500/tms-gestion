@php
    $sections = [
        ['1. Saisir vos opérations quotidiennes', [
            'Ouvrez le <strong>Journal des transactions</strong> et cliquez sur « + Nouvelle écriture » : date, catégorie, type (Entrée / Dépense), description, montant et mode de paiement.',
            'Le mois et le compte (Caisse pour les espèces, Banque pour le reste) sont calculés automatiquement.',
            'Les <strong>ventes</strong> ajoutent seules leur ligne au Journal (« Vente - CLIENT (n article(s)) ») ; elle se met à jour ou disparaît avec la vente.',
            'Les <strong>services</strong> (ABA, Photographie, Infographie, Sérigraphie...) se saisissent dans le module Services : Entrée et/ou Dépense, le bénéfice s\'affiche pendant la saisie.',
        ]],
        ['2. Consulter le tableau de bord', [
            'L\'<strong>Accueil</strong> reprend la feuille ACCUEIL : les gros boutons de section à gauche et la grille de modules. Seuls les modules autorisés pour votre profil apparaissent.',
            'Les indicateurs (chiffre d\'affaires, achats, créances, dettes, stock...) se recalculent à chaque ouverture.',
        ]],
        ['3. Consulter un mois précis, la synthèse ou les statistiques', [
            '<strong>Rapport mensuel</strong> : choisissez un mois, tout le rapport (catégories, répartition du bénéfice, acquisitions, reste à investir) se recalcule.',
            '<strong>Synthèse</strong> : totaux généraux et tableau mois par mois.',
            '<strong>Statistiques</strong> : meilleur / plus faible mois, marge moyenne, performance par catégorie, top 10 des acquisitions.',
        ]],
        ['4. Émettre un document commercial', [
            'Proforma (devis), Facture, Reçu, Bordereau de livraison ou Bon de commande (ce dernier s\'adresse à un fournisseur).',
            'Le numéro est attribué automatiquement à l\'enregistrement ; le total et le montant en lettres se calculent seuls.',
            'Bouton « Imprimer » puis « Enregistrer en PDF » dans la fenêtre d\'impression du navigateur.',
            'Tous les documents apparaissent automatiquement dans le <strong>Registre des documents</strong> : inutile de les reporter à la main.',
        ]],
        ['5. Clients, fournisseurs, commandes et stock', [
            'Clients et Fournisseurs : fiches avec téléphone, email, adresse.',
            'Commandes : une ligne par commande client avec son statut (En attente, En cours, Exécutée, Livrée, Annulée).',
            'Articles : le stock disponible = stock initial + achats − ventes ; une alerte apparaît sous le stock minimum. Une vente supérieure au stock est refusée.',
        ]],
        ['6. Créances clients et dettes fournisseurs', [
            'Un paiement reçu se note dans <strong>Paiements clients</strong> ; choisissez la facture réglée pour que le Registre calcule le reste à payer et le statut (Payée / Partiellement payée / Impayée).',
            'Un achat à crédit se saisit avec le mode de règlement « Crédit » ; chaque règlement se note dans <strong>Règlements fournisseurs</strong>.',
        ]],
        ['7. Suivre la trésorerie', [
            'Indiquez vos soldes de départ (caisse et banque) dans <strong>Configuration entreprise</strong>.',
            'La <strong>Trésorerie</strong> affiche les soldes en temps réel, la vue d\'un jour au choix et la synthèse mensuelle avec solde cumulé.',
            'Un encaissement de créance passe par les Paiements clients : ne le ressaisissez pas dans le Journal (double comptage).',
        ]],
        ['8. Répartition du bénéfice', [
            'Les pourcentages Investissement / Épargne / Loyer / Fonctionnement se règlent dans Configuration entreprise ; Synthèse et Rapport mensuel suivent seuls.',
        ]],
        ['9. Sauvegarder et archiver', [
            '<strong>Archives</strong> : « Archiver le mois » fige les chiffres du mois ; chaque archive se télécharge (fichiers CSV lisibles dans Excel).',
            '« Télécharger la sauvegarde » produit un ZIP de toutes vos données : rangez-le sur une clé USB ou un dossier cloud.',
            'Une <strong>sauvegarde automatique</strong> est faite chaque nuit à 2 h ; les 30 dernières restent téléchargeables dans Archives.',
        ]],
        ['9 bis. Envoyer les documents et relancer les clients', [
            'Dans le <strong>Registre des documents</strong> : « PDF » ouvre le document au format PDF, « Email » l\'envoie au client avec le PDF joint (le client doit avoir une adresse email sur sa fiche), « WhatsApp » prépare un message.',
            'Page <strong>Relances clients</strong> : toutes les factures en retard, avec le nombre de jours, la dernière relance et les boutons « Relancer par email » et « WhatsApp ».',
            'Dans cette même page, deux options : envoyer automatiquement chaque nouveau document par email, et relancer automatiquement chaque matin à 8 h les clients en retard (délais réglables).',
        ]],
        ['9 ter. Caisse rapide avec code-barres', [
            'Renseignez le <strong>code-barres</strong> de chaque article (fiche article : cliquez dans le champ et passez la douchette sur le produit).',
            'À la caisse (Ventes), passez la douchette : l\'article s\'ajoute au panier, un deuxième passage augmente la quantité. Un bip confirme chaque lecture.',
            'Sans douchette, tapez le code de l\'article puis Entrée, ou utilisez le bouton « 📷 Caméra » sur un téléphone Android.',
        ]],
        ['9 quater. Installer l\'application sur le téléphone', [
            'Sur Android (Chrome) : bouton vert « Installer l\'application » en bas du menu, ou menu ⋮ du navigateur → « Installer l\'application ».',
            'Sur iPhone (Safari) : bouton Partager → « Sur l\'écran d\'accueil ».',
            'L\'application s\'ouvre alors en plein écran comme une application classique. Avec une connexion faible, les pages déjà consultées s\'affichent depuis le téléphone ; les enregistrements, eux, nécessitent la connexion (un bandeau orange prévient en cas de coupure).',
        ]],
        ['10. Utilisateurs, droits et audit', [
            'Chaque personne a son compte et son profil (Administrateur, Directeur, Comptable, Caissier, Magasinier, Commercial, Consultation).',
            'Les droits se règlent dans <strong>Paramètres & droits</strong>, sans programmation.',
            'Le <strong>Journal d\'activité</strong> trace connexions, créations, modifications, suppressions et impressions ; l\'<strong>Audit</strong> montre le détail avant / après de chaque modification ou suppression.',
            'Chacun peut changer son propre mot de passe depuis « Changer le mot de passe » (bas du menu).',
        ]],
    ];
@endphp
<div class="space-y-4">
    <div class="card p-5">
        <h2 class="text-lg font-semibold text-slate-900">Guide d'utilisation</h2>
        <p class="text-sm text-slate-500">{{ auth()->user()->entreprise?->nom ?? config('app.name') }} — plateforme de gestion</p>
    </div>
    @foreach ($sections as [$titre, $points])
        <div class="card p-5">
            <h3 class="mb-3 font-semibold text-[#1f3864]">{{ $titre }}</h3>
            <ul class="list-disc space-y-1.5 pl-5 text-sm text-slate-700">
                @foreach ($points as $point)
                    <li>{!! $point !!}</li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
