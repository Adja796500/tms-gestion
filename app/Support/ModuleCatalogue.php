<?php

namespace App\Support;

class ModuleCatalogue
{
    /**
     * Description en langage clair de chaque module implemente, utilisee
     * sur la page "Mon role" pour expliquer a chaque utilisateur ce qu'il
     * peut faire concretement sur la plateforme.
     */
    public const MODULES = [
        'ACCUEIL' => [
            'label' => 'Tableau de bord',
            'description' => "Voir la vue d'ensemble de l'entreprise : chiffre d'affaires, achats, stock, créances, dettes, alertes.",
        ],
        'ARTICLES' => [
            'label' => 'Articles & stock',
            'description' => 'Gérer la liste des articles (prix, catégories) et suivre le stock disponible.',
        ],
        'ACHATS_STOCK' => [
            'label' => 'Achats',
            'description' => 'Enregistrer les achats de marchandises auprès des fournisseurs (entrées de stock).',
        ],
        'VENTES' => [
            'label' => 'Ventes',
            'description' => "Enregistrer les ventes realisees aux clients, au jour le jour.",
        ],
        'VENTE_RAPIDE' => [
            'label' => 'Caisse (vente au comptoir)',
            'description' => "Encaisser les ventes au comptoir, les modifier si le client change d'avis, créer rapidement un article ou un client, imprimer le reçu et gérer la monnaie à rendre.",
        ],
        'CLIENTS' => [
            'label' => 'Clients',
            'description' => 'Gérer les fiches clients (coordonnées).',
        ],
        'FOURNISSEURS' => [
            'label' => 'Fournisseurs',
            'description' => 'Gérer les fiches fournisseurs (coordonnées).',
        ],
        'PAIEMENTS_CLIENTS' => [
            'label' => 'Paiements clients',
            'description' => 'Enregistrer les paiements reçus des clients.',
        ],
        'REGLEMENTS_FOURNISSEURS' => [
            'label' => 'Règlements fournisseurs',
            'description' => 'Enregistrer les sommes versees aux fournisseurs.',
        ],
        'CREANCES_CLIENTS' => [
            'label' => 'Créances clients',
            'description' => 'Consulter ce que les clients doivent encore payer (vue calculée automatiquement).',
        ],
        'DETTES_FOURNISSEURS' => [
            'label' => 'Dettes fournisseurs',
            'description' => "Consulter ce qui est encore du aux fournisseurs (vue calculée automatiquement).",
        ],
        'FACTURE_PROFORMA' => [
            'label' => 'Factures proforma',
            'description' => 'Émettre des devis / factures proforma pour les clients.',
        ],
        'FACTURE_DEFINITIVE' => [
            'label' => 'Factures définitives',
            'description' => 'Émettre des factures définitives pour les clients.',
        ],
        'RECU' => [
            'label' => 'Reçus',
            'description' => "Générer un reçu à partir d'un paiement client déjà enregistre.",
        ],
        'UTILISATEURS' => [
            'label' => 'Utilisateurs',
            'description' => 'Créer et gérer les comptes des utilisateurs de la plateforme.',
        ],
        'JOURNAL' => [
            'label' => "Journal d'activité",
            'description' => "Consulter l'historique de toutes les actions de tous les utilisateurs (connexions, creations, modifications, suppressions, impressions).",
        ],
        'PARAMETRES' => [
            'label' => 'Paramètres & droits',
            'description' => "Consulter, et pour l'administrateur modifier, les droits d'accès de chaque profil.",
        ],
        'CONFIGURATION_ENTREPRISE' => [
            'label' => 'Configuration entreprise',
            'description' => "Gérer les coordonnées, informations fiscales, taux de répartition et soldes initiaux de l'entreprise.",
        ],
        'CATEGORIES_ACTIVITE' => [
            'label' => "Catégories d'activité",
            'description' => 'Gérer les catégories utilisées pour classer les articles et les écritures du journal comptable.',
        ],
        'COMPTABILITE_JOURNAL' => [
            'label' => 'Journal comptable',
            'description' => 'Enregistrer les entrées et dépenses de caisse/banque, au jour le jour.',
        ],
        'COMPTABILITE_ACQUISITIONS' => [
            'label' => 'Acquisitions & prêts',
            'description' => 'Enregistrer les acquisitions, prêts sur bénéfices et dépenses sur réserve.',
        ],
        'SERVICES' => [
            'label' => 'Services',
            'description' => 'Enregistrer les entrées et dépenses des services (ABA, Photographie, Infographie, Sérigraphie...) et suivre leur bénéfice.',
        ],
        'TRESORERIE' => [
            'label' => "Trésorerie",
            'description' => "Suivre les soldes de caisse et de banque, la vue d'un jour et la synthèse mensuelle de trésorerie.",
        ],
        'RAPPORT MENSUEL' => [
            'label' => "Rapport mensuel",
            'description' => "Analyser un mois : résultat par catégorie, répartition du bénéfice, acquisitions et reste à investir.",
        ],
        'SYNTHESE' => [
            'label' => "Synthèse",
            'description' => "Voir les totaux généraux et le tableau mois par mois (entrées, dépenses, bénéfice, répartition).",
        ],
        'STATISTIQUES' => [
            'label' => "Statistiques",
            'description' => "Consulter les indicateurs clés : meilleur et plus faible mois, marge, performance par catégorie, top acquisitions.",
        ],
        'BORDEREAU_LIVRAISON' => [
            'label' => "Bordereaux de livraison",
            'description' => "Émettre et imprimer les bordereaux de livraison remis aux clients.",
        ],
        'BON_COMMANDE' => [
            'label' => "Bons de commande",
            'description' => "Émettre et imprimer les bons de commande adresses aux fournisseurs.",
        ],
        'REGISTRE_DOCUMENTS' => [
            'label' => "Registre des documents",
            'description' => "Consulter tous les documents emis, leur statut et ce qui reste a encaisser.",
        ],
        'ARCHIVES' => [
            'label' => "Archives & sauvegardes",
            'description' => "Clôturer un mois, télécharger ses mouvements et la sauvegarde complète des données.",
        ],
        'AUDIT' => [
            'label' => "Audit",
            'description' => "Consulter le détail avant/après de toutes les modifications et suppressions.",
        ],
        'GUIDE' => [
            'label' => "Guide d'utilisation",
            'description' => "Lire le mode d'emploi de la plateforme.",
        ],
        'COMMANDES' => [
            'label' => 'Commandes',
            'description' => 'Enregistrer et suivre les commandes clients jusqu\'a leur livraison.',
        ],
    ];

    /**
     * Description courte du role de chaque profil, affichee en haut de la
     * page "Mon role".
     */
    public const PROFILS = [
        'Administrateur' => "Acces complet a toute la plateforme : tous les modules, gestion des utilisateurs et des droits, consultation du journal d'activite.",
        'Directeur' => 'Supervision : consultation de tous les modules operationnels et financiers, sans pouvoir creer/modifier/supprimer de donnees.',
        'Comptable' => 'Gestion financiere : clients, fournisseurs, paiements, reglements, creances/dettes et facturation.',
        'Caissier' => 'Encaissement au quotidien : clients, paiements clients, factures definitives et recus.',
        'Magasinier' => 'Gestion du stock : articles, achats de marchandises, ventes et fournisseurs.',
        'Commercial' => 'Relation client et devis : fiches clients et factures proforma.',
        'Consultation' => 'Lecture seule sur les modules qui vous sont ouverts, sans pouvoir creer/modifier/supprimer de donnees.',
    ];
}
