#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# Bob — Deploy (run on every subsequent update)
# Triggered automatically by GitHub Actions, or run manually:
#   sudo -u www-data bash /var/www/bob/scripts/deploy.sh
# ─────────────────────────────────────────────────────────────────────────────
set -e

APP_DIR="/var/www/bob"

cd "$APP_DIR"

echo "==> Pulling latest code..."
git pull origin main

echo "==> Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Installing Node dependencies and building assets..."
npm ci
npm run build

echo "==> Setting permissions..."
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 755 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Linking storage (safe to re-run)..."
php artisan storage:link --force 2>/dev/null || true

echo "==> Caching for production..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Reloading services..."
systemctl reload php8.2-fpm
supervisorctl restart bob-worker:*

echo ""
echo "======================================================"
echo " Deploy COMPLETE — $(date '+%Y-%m-%d %H:%M:%S')"
echo "======================================================"
