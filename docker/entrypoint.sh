#!/bin/sh

echo "🚀 Container başlıyor..."

# Apache MPM fix
a2dismod mpm_event || true
a2dismod mpm_worker || true
a2enmod mpm_prefork || true

# .env yoksa oluştur
if [ ! -f .env ]; then
  cp .env.example .env
fi

# APP_KEY yoksa üret
if ! grep -q "APP_KEY=base64" .env; then
  php artisan key:generate
fi

# Migration
php artisan migrate --force || true

# Cache
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

chown -R www-data:www-data /var/www/html

echo "✅ Laravel hazır!"

apache2-foreground