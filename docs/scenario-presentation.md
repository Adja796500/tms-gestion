# Scénario de présentation de la plateforme (≈ 15 minutes)

Objectif : montrer au propriétaire que tout ce que faisait le classeur Excel est là, en
plus simple, plus sûr, et avec ce qu'Excel ne pouvait pas faire.

On présente avec **l'entreprise de démonstration** (données réalistes sur 4 mois), pas avec
les vraies données de Rob : les graphiques et les relances y ont du sens.

---

## Avant la présentation (la veille)

1. **Recréer une démonstration toute fraîche** (dates à jour par rapport au jour J) :
   ```
   php artisan demo:creer --reinitialiser
   ```
2. **Lancer la plateforme** : double-clic sur `demarrer-plateforme.bat` (à la racine du projet).
   Chrome s'ouvre sur http://127.0.0.1:8010. Laisser la fenêtre noire ouverte.
3. **Vérifier les 2 comptes de démonstration** :

   | Compte | Utilisateur | Mot de passe |
   |---|---|---|
   | Administrateur | `demo` | `Demo2026!` |
   | Caissier | `demo-caisse` | `Demo2026!` |

4. **Faire une sauvegarde** de sécurité (Archives → Télécharger la sauvegarde) sur clé USB.
5. Prévoir si possible un **second écran ou un vidéoprojecteur**, et le téléphone du propriétaire
   (pour lui envoyer un vrai message WhatsApp de démonstration).

> À savoir pour ne pas être surpris :
> - Un bandeau jaune « Les emails ne partent pas encore réellement » apparaît dans les relances :
>   c'est normal tant que le serveur en ligne et son adresse email ne sont pas configurés. Le dire.
> - L'installation sur téléphone exige un site en **https** : elle se montrera une fois en ligne.
>   Pendant la démonstration, on peut l'installer **sur l'ordinateur** (Chrome → bouton vert
>   « Installer l'application » en bas du menu), ce qui montre le principe.

---

## Le déroulé

### 1. La connexion et la sécurité — 1 min
- Page de connexion au logo TMS. Se connecter avec **`demo`**.
- *À dire :* « Chaque employé a son propre compte. Fini le fichier Excel partagé où tout le
  monde voit et modifie tout, et dont les mots de passe étaient lisibles dans le fichier. »

### 2. Le tableau de bord — 2 min
- Le bandeau, les **7 gros boutons** et la **grille bleue** : « C'est votre feuille ACCUEIL. »
- Le panneau **« À traiter »** : factures non soldées, stock bas, monnaie à rendre, commandes.
  *« L'application vous dit chaque matin ce qui demande votre attention. »*
- Les **chiffres du mois** comparés au mois précédent (▲/▼).
- Le **graphique** : survoler un mois pour le détail. « Voir le tableau » pour les chiffres.
- **Entrées par activité** : la part de la vente, de la sérigraphie, de la photographie…

### 3. Une vente à la caisse — 2 min
- Menu **Ventes & clients → Caisse / Nouvelle vente**.
- Dans « Scanner un article », taper **`A002`** puis Entrée → le stylo s'ajoute (bip).
  Re-taper `A002` → la quantité passe à 2. (Avec une douchette, c'est un simple « bip ».)
- Choisir un client, saisir le montant remis : la **monnaie à rendre** se calcule.
- Enregistrer → le **reçu** s'affiche, prêt à imprimer.
- *À dire :* « Le stock, le journal et la trésorerie se sont mis à jour tout seuls. »

### 4. Du devis à la facture, en un clic — 2 min
- **Facturation → Proformas / devis** : sur la proforma « En attente », cliquer **« → Facturer »**.
  La facture est créée avec les mêmes lignes, la proforma passe en « Acceptée ».
- **Facturation → Registre des documents** : la nouvelle facture y est déjà.
- Cliquer **PDF** : la facture au logo de l'entreprise, montant en lettres.
- Cliquer **WhatsApp** : le message est déjà rédigé (numéro, montant, reste à payer).

### 5. Encaisser une facture — 1 min
- Toujours dans le Registre, sur une facture « Impayée » : **Encaisser**.
- Le montant restant est proposé ; « Générer le reçu » est coché → Enregistrer.
- La facture passe en « Payée », le reçu est créé. *« Plus aucun report manuel entre feuilles. »*

### 6. Les relances — 1 min
- **Ventes & clients → Relances clients** : les factures en retard, du plus ancien au plus récent,
  avec la couleur selon le retard (40 jours, 33 jours…).
- Montrer les **réglages d'envoi automatique** : relance chaque matin à 8 h.
- *À dire :* « Les clients en retard sont relancés tout seuls, poliment, avec la facture jointe. »

### 7. Les chiffres de gestion — 2 min
- **Finances → Rapport mensuel** : choisir un mois ; catégories, répartition du bénéfice
  (55 / 10 / 5 / 30 %), acquisitions, reste à investir. *« C'est votre feuille RAPPORT MENSUEL. »*
- **Finances → Trésorerie** : soldes caisse / banque en temps réel, vue d'un jour, mois par mois.
- **Finances → Journal des transactions** : bouton **« Ce mois-ci »**, filtres, totaux,
  **Exporter (Excel)** : « Vous gardez Excel quand vous en avez besoin. »

### 8. Les droits par profil — 1 min
- Se déconnecter, se reconnecter avec **`demo-caisse`**.
- Le menu est réduit à ce qui concerne le caissier : pas de trésorerie, pas de rapports,
  pas d'administration. *« Chacun voit uniquement ce qui le concerne. »*

### 9. La sécurité des données — 1 min
- Se reconnecter en `demo`. **Administration → Audit** : chaque modification et suppression,
  avec qui, quand, et la valeur avant / après.
- **Administration → Archives & sauvegardes** : sauvegarde automatique chaque nuit,
  téléchargeable. *« Si un ordinateur tombe en panne, rien n'est perdu. »*

### 10. Conclusion — 1 min
- Ce qui reste à décider : **le serveur en ligne** (adresse web, email d'envoi).
- Une fois en ligne : accessible partout, installable sur téléphone, emails et relances actifs.

---

## Questions probables et réponses

| Question | Réponse |
|---|---|
| « Et mes données Excel ? » | Elles ont été reprises dans la plateforme (ventes, articles, clients, journal…). |
| « Et si internet coupe ? » | Les pages déjà ouvertes restent consultables ; un bandeau prévient. Les enregistrements reprennent au retour du réseau. |
| « Je peux l'utiliser sur mon téléphone ? » | Oui, et l'installer comme une application dès que le site est en ligne (https). |
| « Mes employés peuvent-ils tricher ? » | Chaque action est tracée dans l'Audit, avec la valeur avant/après. Les droits se règlent par profil. |
| « Et pour une autre entreprise ? » | La plateforme accueille plusieurs entreprises, chacune avec ses données, son logo et ses comptes, totalement séparés. |
| « Combien ça coûte de la mettre en ligne ? » | Un abonnement d'hébergement mensuel + un nom de domaine annuel (à chiffrer selon l'hébergeur choisi). |
