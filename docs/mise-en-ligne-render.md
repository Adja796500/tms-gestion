# Mise en ligne gratuite sur Render

Render héberge gratuitement la plateforme (site web + base PostgreSQL) à partir du dépôt
GitHub. Tout est déjà configuré dans `render.yaml` et `Dockerfile` : il n'y a qu'à cliquer.

## Étapes (≈ 10 minutes, dont 5 à 8 d'attente)

1. Aller sur **https://render.com** → **Get Started** → **GitHub** (se connecter avec le compte
   GitHub qui possède le dépôt `tms-gestion`). Aucune carte bancaire n'est demandée pour l'offre gratuite.
2. Dans Render : **New +** → **Blueprint**.
3. Autoriser Render à accéder au dépôt **tms-gestion**, puis le sélectionner.
4. Render lit `render.yaml` et affiche : un service web `tms-gestion` et une base `tms-gestion-db`.
   Il demande la valeur de **APP_KEY** : coller la clé fournie (commence par `base64:`).
5. Cliquer **Apply** / **Deploy Blueprint**. Le premier déploiement prend 5 à 8 minutes
   (construction de l'image, puis création des comptes et de l'entreprise de démonstration).
6. Quand le statut passe à **Live**, le lien est affiché en haut : `https://tms-gestion-xxxx.onrender.com`.

## Comptes disponibles après le premier démarrage

| Compte | Utilisateur | Mot de passe |
|---|---|---|
| Démonstration (administrateur) | `demo` | `Demo2026!` |
| Démonstration (caissier) | `demo-caisse` | `Demo2026!` |
| Rob Teegwende (données d'exemple) | `admin` | `Admin2026!` |
| Super-administrateur | `superadmin` | `SuperAdmin2026!` |

⚠️ Le site étant public, **changer ces mots de passe** dès la mise en ligne
(« Changer le mot de passe » en bas du menu).

## Limites de l'offre gratuite (à connaître)

- **Mise en veille** : après 15 minutes sans visite, le site s'endort ; la visite suivante le
  réveille en 30 à 60 secondes. Ouvrir le lien une minute avant une présentation.
- **Base gratuite limitée dans le temps** : la base PostgreSQL gratuite de Render expire au bout
  d'environ 30 jours. Pour un usage durable, passer à une base payante ou à une base gratuite
  permanente (ex. Neon) — la plateforme n'a qu'une variable à changer (`DB_URL`).
- **Fichiers envoyés non conservés** : logos et photos d'articles envoyés sont perdus à chaque
  redéploiement (disque temporaire). Les recharger si besoin, ou passer à un disque persistant.
- **Emails** : non envoyés tant qu'un compte SMTP n'est pas renseigné (variables `MAIL_*`).

## Mettre à jour le site

Le site suit la branche **`production`** du dépôt. Pour publier une nouvelle version :
```
git checkout production
git merge main
git push
git checkout main
```
Render redéploie automatiquement en quelques minutes.
