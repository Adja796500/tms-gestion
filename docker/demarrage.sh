#!/bin/sh
# Demarrage du conteneur : prepare la base puis lance Apache.
set -e

php artisan config:clear
php artisan migrate --force
php artisan storage:link 2>/dev/null || true

# Premier demarrage (base vide) : comptes, droits, donnees d'exemple et entreprise de demonstration.
if [ "$(php artisan tinker --execute='echo \App\Models\User::withoutGlobalScopes()->count();' 2>/dev/null | tail -n 1)" = "0" ]; then
    echo "Base vide : chargement des donnees initiales..."
    php artisan db:seed --force
    php artisan demo:creer || true
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
