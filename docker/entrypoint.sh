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

# ❌ DB kullanmıyorsun → migrate KALDIRILDI

# Cache temiz (çok önemli)
php artisan config:clear || true
php artisan cache:clear || true

# Cache build
php artisan config:cache || true

# Permission fix
chown -R www-data:www-data /var/www/html
chmod -R 777 storage bootstrap/cache

echo "✅ Laravel hazır!"

apache2-foreground