#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# Bob — First deploy (run once on the server after setup-server.sh)
# Run as root:  bash first-deploy.sh https://github.com/YOU/bob.git
# ─────────────────────────────────────────────────────────────────────────────
set -e

REPO_URL=${1:?"Usage: $0 <git-repo-url>"}
APP_DIR="/var/www/bob"

echo "==> Cloning repository..."
git clone "$REPO_URL" "$APP_DIR"
chown -R www-data:www-data "$APP_DIR"

cd "$APP_DIR"

echo "==> Installing PHP dependencies..."
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Installing Node dependencies and building assets..."
npm ci
npm run build

echo "==> Setting up .env..."
cp .env.example .env
echo ""
echo "──────────────────────────────────────────────────"
echo " Edit $APP_DIR/.env now with your settings:"
echo "   APP_ENV=production"
echo "   APP_DEBUG=false"
echo "   APP_URL=https://yourdomain.com"
echo "   APP_KEY=  (will be generated below)"
echo "   DB_CONNECTION=mysql"
echo "   DB_DATABASE=bob"
echo "   DB_USERNAME=bob"
echo "   DB_PASSWORD=<from setup-server.sh output>"
echo "   MAIL_MAILER=smtp ..."
echo "──────────────────────────────────────────────────"
echo ""
read -p "Press ENTER after editing .env to continue..."

echo "==> Generating app key..."
php artisan key:generate

echo "==> Setting permissions..."
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR/storage"
chmod -R 755 "$APP_DIR/bootstrap/cache"

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Linking storage..."
php artisan storage:link

echo "==> Caching for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Restarting services..."
systemctl restart php8.2-fpm
supervisorctl restart bob-worker:*

echo ""
echo "======================================================"
echo " First deploy COMPLETE — visit https://yourdomain.com"
echo "======================================================"
