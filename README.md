# ENTREPRISE DAME — Plateforme de gestion (Laravel + PostgreSQL)

Cette application reprend le classeur Excel `ENTREPRISE_DAME_ODG_v33b_SYNTHESE_v2_1.xlsm`
et le transforme en plateforme web : Laravel 11, PostgreSQL, interface Blade + Livewire 3
(Tailwind CSS), authentification par utilisateur/mot de passe, et droits d'accès par profil
identiques à la feuille `DROITS_ACCES` du fichier d'origine.

## Périmètre de cette première version (MVP)

Modules livrés et fonctionnels :

- Tableau de bord (chiffre d'affaires, achats, créances, dettes, alertes de stock)
- Articles & stock (avec calcul automatique du stock disponible)
- Achats de stock (met à jour le stock et le statut de règlement)
- Ventes (met à jour le stock)
- Clients / Fournisseurs
- Paiements clients / Règlements fournisseurs
- Créances clients / Dettes fournisseurs (vues calculées automatiquement)
- Factures proforma / définitives (génération de documents multi-lignes + impression PDF via le navigateur)
- Reçus (générés à partir d'un paiement client)
- Utilisateurs (gestion des comptes, réservée à l'administrateur)
- Paramètres & matrice des droits d'accès (lecture)

Modules ajoutés ensuite pour reprendre le reste du classeur :

- Journal des transactions (les ventes y ajoutent automatiquement leur ligne d'entrée) et Services
- Trésorerie, Rapport mensuel, Synthèse, Statistiques (calculs centralisés dans `app/Support/Finances.php`)
- Bordereaux de livraison, Bons de commande, Registre des documents (montant payé / reste / statut de créance)
- Archives mensuelles et sauvegarde complète (ZIP de CSV), Audit (avant/après), Guide, changement de mot de passe

Non repris volontairement : la licence par machine du classeur (sans objet pour une application web,
l'accès est contrôlé par les comptes utilisateurs) et la restauration d'une sauvegarde depuis l'écran
(à faire par l'administrateur technique, pour éviter un écrasement accidentel des données).

## Pile technique

- PHP 8.2+, Laravel 11
- PostgreSQL
- Livewire 3 + Tailwind CSS (Vite)
- Authentification par session (nom d'utilisateur + mot de passe), pas de Breeze/Jetstream :
  volontairement minimal pour rester 100% Laravel sans dépendance supplémentaire.

## Important — sécurité des mots de passe

Dans le classeur Excel d'origine, les mots de passe des utilisateurs étaient stockés **en clair**
(feuille `UTILISATEURS`). Dans cette plateforme, ils sont systématiquement hachés (bcrypt) dès
l'import. Les mots de passe listés ci-dessous sont ceux qui existaient dans le fichier source :
**changez-les dès la première connexion**, en particulier si l'un d'entre eux est réutilisé ailleurs.

## Installation locale

Prérequis : PHP >= 8.2 avec les extensions `pdo_pgsql`, `mbstring`, `xml`, `curl` ; Composer ;
Node.js >= 18 ; un serveur PostgreSQL accessible.

```bash
# 1. Installer les dépendances PHP et JS
composer install
npm install

# 2. Configurer l'environnement
cp .env.example .env
php artisan key:generate
# Éditer .env : DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 3. Créer la base PostgreSQL (exemple avec psql)
createdb dame_erp

# 4. Lancer les migrations + import des données réelles du classeur Excel
php artisan migrate --seed

# 5. Compiler les assets (une fois) puis lancer le serveur
npm run build
php artisan serve
```

L'application est alors disponible sur http://localhost:8000.

Pour développer avec le rechargement à chaud des assets, lancez `npm run dev` dans un
terminal séparé pendant que `php artisan serve` tourne.

## Comptes de connexion (importés du classeur, mots de passe à changer)

| Utilisateur   | Mot de passe     | Profil          |
|---------------|------------------|-----------------|
| admin         | Admin2026!       | Administrateur  |
| directeur     | Direction2026!   | Directeur       |
| comptable     | Compta2026!      | Comptable       |
| caissier      | Caisse2026!      | Caissier        |
| magasinier    | Stock2026!       | Magasinier      |
| commercial    | Vente2026!       | Commercial      |
| consultation  | Lecture2026!     | Consultation    |

## Données importées

Le seeder (`database/seeders/`) lit `database/seeders/data/seed_data.json`, qui contient les
données réelles extraites du classeur : 2 articles, 2 clients déclarés (+ clients complémentaires
détectés dans les ventes mais absents de la feuille CLIENTS d'origine — incohérence du fichier
source, corrigée automatiquement à l'import), 3 fournisseurs, 7 comptes utilisateurs, les achats
et ventes déjà enregistrés. Les compteurs de stock, créances et dettes sont recalculés depuis ces
mouvements plutôt que recopiés tels quels (les formules Excel n'étaient plus fiables sur certaines
lignes du fichier d'origine).

## Droits d'accès par profil

La table `access_rules` reproduit exactement la feuille `DROITS_ACCES` du classeur (28 modules x
7 profils). Le middleware `module:CODE_MODULE` (voir `app/Http/Middleware/CheckModuleAccess.php`)
bloque l'accès aux routes non autorisées pour le profil de l'utilisateur connecté ; le profil
Administrateur a toujours accès à tout. La page **Paramètres** affiche cette matrice.

## Vérifications effectuées

Le développement de cette plateforme s'est fait dans un environnement sans PHP ni PostgreSQL
disponibles (bac à sable sans droits d'installation système). Il n'a donc pas été possible d'exécuter
`composer install` / `php artisan migrate` / les tests automatisés dans cet environnement. Pour
compenser :

- Les 95 fichiers PHP du projet ont été vérifiés syntaxiquement avec un interpréteur PHP 8.4 réel
  (via PHP compilé en WebAssembly), en confirmant qu'aucun n'a d'erreur de syntaxe.
- Les directives Blade (`@if/@endif`, `@foreach/@endforeach`, etc.) ont été vérifiées équilibrées
  dans les 19 vues du projet.
- Une suite de tests PHPUnit est fournie (`tests/Feature`, `tests/Unit`) couvrant l'authentification,
  les droits d'accès par module et le recalcul du stock. **Faites tourner `php artisan test` après
  l'installation** pour confirmer que tout fonctionne dans votre environnement réel avant mise en
  production.

## Prochaines étapes suggérées

1. `php artisan test` en local pour valider le comportement réel avec PostgreSQL.
2. Changer tous les mots de passe importés.
3. Étendre les modules restants (Trésorerie, Journal, Commandes, Statistiques, Audit...) en
   suivant le modèle des modules déjà livrés (un `Livewire\Component`, une vue Blade, une entrée
   de route protégée par `module:CODE`).
4. Ajouter une génération PDF serveur (ex. `barryvdh/laravel-dompdf`) si l'impression navigateur
   des factures/reçus ne suffit pas.

## Fonctions automatiques et application mobile

### Email (factures en PDF et relances)

Les documents sont générés en PDF côté serveur (`barryvdh/laravel-dompdf`) et envoyés
avec le PDF en pièce jointe. Par défaut `MAIL_MAILER=log` : **aucun email ne part**,
les messages sont seulement écrits dans `storage/logs/laravel.log`. Pour envoyer
réellement, renseignez un compte SMTP dans `.env` (exemple Gmail dans `.env.example`),
puis `php artisan config:clear`.

### Tâches de nuit : sauvegarde (2 h) et relances (8 h)

Définies dans `routes/console.php`. Le serveur doit exécuter le planificateur
Laravel **chaque minute** :

- Linux (cron) : `* * * * * cd /chemin/du/projet && php artisan schedule:run >> /dev/null 2>&1`
- Windows (Planificateur de tâches), en invite de commandes administrateur :

  ```
  schtasks /Create /TN "Gestion ODG - planificateur" /SC MINUTE /MO 1 /TR "C:\xampp\php\php.exe C:\chemin\du\projet\artisan schedule:run" /RU SYSTEM
  ```

Commandes manuelles utiles : `php artisan sauvegarde:automatique`,
`php artisan relances:envoyer --simulation` (liste sans envoyer), `php artisan schedule:list`.
Les sauvegardes automatiques sont rangées dans `storage/app/sauvegardes/{entreprise}`
(30 conservées) et téléchargeables depuis Archives.

### Application installable (PWA)

`public/manifest` (route `/manifest.webmanifest`), `public/sw.js`, `public/offline.html`
et `public/icons/`. Installable depuis Chrome (Android, PC) ou Safari (iPhone).
**En production, le site doit être servi en HTTPS**, sinon les navigateurs
refusent l'installation (seul `localhost` / `127.0.0.1` fait exception). Les pages et
fichiers consultés sont mis en cache pour les connexions faibles ; les enregistrements
nécessitent toujours le réseau. Après une modification de `sw.js`, incrémentez sa
constante `VERSION` pour forcer la mise à jour chez les utilisateurs.

### Caisse rapide (code-barres)

Champ `code_barre` sur les articles. Une douchette USB/Bluetooth se comporte comme un
clavier : dans la zone « Scanner un article » de la caisse, elle tape le code puis
Entrée et l'article est ajouté au panier. La lecture par caméra utilise l'API
`BarcodeDetector` (Chrome / Android) ; le bouton n'apparaît que si elle est disponible.

## Démonstration

- `php artisan demo:creer` crée l'« Entreprise Démo » : 4 mois d'activité cohérente (ventes,
  services, achats, factures payées ou en retard, photos d'articles). Comptes : `demo` et
  `demo-caisse`, mot de passe `Demo2026!`. `--reinitialiser` la supprime et la recrée (dates à jour).
- `php artisan exemples:ajouter "Nom de l'entreprise" --mois=3` AJOUTE une activité d'illustration
  à une entreprise existante (ventes, services, factures…), sans rien supprimer. Faire une sauvegarde avant.
- `demarrer-plateforme.bat` (Windows) lance la plateforme en local sur http://127.0.0.1:8010.
- Déroulé conseillé pour une présentation : `docs/scenario-presentation.md`.
