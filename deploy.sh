#!/usr/bin/env bash
# Script de déploiement — à lancer depuis le terminal SSH cPanel LWS,
# à la racine de l'application Laravel, après chaque mise à jour du code.
#
# Usage : bash deploy.sh

set -e

# Si une étape échoue, ne pas laisser le site bloqué en mode maintenance.
trap 'echo "!! Échec du déploiement : sortie du mode maintenance."; php artisan up' ERR

echo "==> Mode maintenance activé"
php artisan down || true

echo "==> Installation des dépendances PHP (production)"
composer install --no-dev --optimize-autoloader --no-interaction

if command -v npm >/dev/null 2>&1; then
    echo "==> Build des assets front (Vite)"
    # Pas de package-lock.json versionné à la racine : npm ci échouerait.
    if [ -f package-lock.json ]; then npm ci; else npm install --no-audit --no-fund; fi
    npm run build
else
    echo "==> npm indisponible sur cet environnement : assurez-vous que public/build"
    echo "    a été généré localement puis uploadé avant de continuer."
fi

echo "==> Lien symbolique storage (si absent)"
php artisan storage:link || true

echo "==> Migrations"
php artisan migrate --force

echo "==> Vignettes des photos d'annonces (seulement celles qui manquent)"
php artisan images:thumbnails || true

echo "==> Mise en cache config/vues"
php artisan config:cache
# Pas de route:cache : tant que le site est servi sous /public
# (https://natontine.com/public/), le cache des routes casse la page
# d'accueil (405 sur "/public/"). On s'assure qu'aucun ancien cache ne reste.
php artisan route:clear
php artisan view:cache
php artisan event:cache

echo "==> Redémarrage des workers de queue"
php artisan queue:restart

echo "==> Sortie du mode maintenance"
php artisan up

echo "==> Déploiement terminé."
