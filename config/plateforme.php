<?php

/*
 * Identite de la PLATEFORME (page de connexion, espace super-administrateur).
 * A ne pas confondre avec le logo de chaque entreprise cliente, qui se regle dans
 * "Configuration entreprise" et apparait dans son menu et sur ses documents.
 * Pour une installation au nom d'une autre societe : deposer ses images dans
 * public/images/ et changer ces valeurs dans le fichier .env.
 */
return [
    'nom' => env('PLATEFORME_NOM', env('APP_NAME', 'Plateforme de gestion')),
    'logo' => env('PLATEFORME_LOGO', '/images/logo.png'),
    'monogramme' => env('PLATEFORME_MONOGRAMME', '/images/logo-marque.png'),
];
