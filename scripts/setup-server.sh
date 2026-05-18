#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# Bob — Compute Engine initial server setup
# Run ONCE as root on a fresh Ubuntu 22.04 VM:
#   bash setup-server.sh yourdomain.com your@email.com
# ─────────────────────────────────────────────────────────────────────────────
set -e

DOMAIN=${1:?"Usage: $0 <domain> <email>"}
EMAIL=${2:?"Usage: $0 <domain> <email>"}
APP_DIR="/var/www/bob"
DB_NAME="bob"
DB_USER="bob"
DB_PASS=$(openssl rand -base64 24)

echo "======================================================"
echo " Setting up bob on: $DOMAIN"
echo "======================================================"

# ── System update ──────────────────────────────────────────────────────────────
apt-get update -y && apt-get upgrade -y
apt-get install -y curl git unzip zip wget software-properties-common ufw

# ── PHP 8.2 ───────────────────────────────────────────────────────────────────
add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y \
    php8.2-fpm php8.2-cli php8.2-mysql php8.2-pgsql php8.2-sqlite3 \
    php8.2-gd php8.2-zip php8.2-curl php8.2-mbstring php8.2-xml \
    php8.2-bcmath php8.2-intl php8.2-opcache php8.2-redis

# ── Nginx ─────────────────────────────────────────────────────────────────────
apt-get install -y nginx

# ── MySQL 8.0 ─────────────────────────────────────────────────────────────────
apt-get install -y mysql-server
mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# ── Node.js 20 ────────────────────────────────────────────────────────────────
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

# ── Composer ──────────────────────────────────────────────────────────────────
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# ── Supervisor (for queue worker) ─────────────────────────────────────────────
apt-get install -y supervisor

# ── Create app directory ───────────────────────────────────────────────────────
mkdir -p "$APP_DIR"
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"

# ── Nginx site config ─────────────────────────────────────────────────────────
cat > /etc/nginx/sites-available/bob <<NGINX
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name $DOMAIN www.$DOMAIN;

    root $APP_DIR/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    client_max_body_size 20M;

    # Versioned assets — cache forever
    location /build {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    # User uploads (avatars)
    location /storage {
        expires 7d;
        add_header Cache-Control "public";
        try_files \$uri =404;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include        fastcgi_params;
        fastcgi_pass   unix:/run/php/php8.2-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/bob /etc/nginx/sites-enabled/bob
rm -f /etc/nginx/sites-enabled/default

# ── PHP-FPM production settings ───────────────────────────────────────────────
cat > /etc/php/8.2/fpm/conf.d/99-bob.ini <<INI
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
upload_max_filesize=20M
post_max_size=20M
max_execution_time=60
memory_limit=256M
expose_php=Off
INI

# ── Supervisor — queue worker ──────────────────────────────────────────────────
cat > /etc/supervisor/conf.d/bob-worker.conf <<SUPERVISOR
[program:bob-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_DIR/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/worker.log
stopwaitsecs=3600
SUPERVISOR

# ── Cron — Laravel scheduler ──────────────────────────────────────────────────
(crontab -l 2>/dev/null; echo "* * * * * www-data cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# ── Firewall ──────────────────────────────────────────────────────────────────
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# ── SSL with Certbot ──────────────────────────────────────────────────────────
snap install --classic certbot
ln -sf /snap/bin/certbot /usr/bin/certbot
# Temporarily allow HTTP for cert challenge
nginx -t && systemctl start nginx
certbot --nginx -d "$DOMAIN" -d "www.$DOMAIN" \
    --non-interactive --agree-tos --email "$EMAIL" \
    --redirect

# ── Restart services ──────────────────────────────────────────────────────────
systemctl restart php8.2-fpm
systemctl restart nginx
systemctl enable php8.2-fpm nginx supervisor mysql
supervisorctl reread && supervisorctl update

echo ""
echo "======================================================"
echo " Server setup COMPLETE"
echo "======================================================"
echo " Domain  : https://$DOMAIN"
echo " App dir : $APP_DIR"
echo " DB name : $DB_NAME"
echo " DB user : $DB_USER"
echo " DB pass : $DB_PASS  ← SAVE THIS!"
echo "======================================================"
echo " Next: run scripts/first-deploy.sh on this server"
echo "======================================================"
