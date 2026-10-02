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

echo "==> Mise en cache config/routes/vues"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Redémarrage des workers de queue"
php artisan queue:restart

echo "==> Sortie du mode maintenance"
php artisan up

echo "==> Déploiement terminé."
