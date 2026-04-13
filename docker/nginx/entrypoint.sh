#!/bin/sh

echo "🚀 Container başlıyor..."

# .env yoksa oluştur
if [ ! -f .env ]; then
  echo ".env oluşturuluyor..."
  cp .env.example .env
fi

# APP_KEY yoksa üret
if ! grep -q "APP_KEY=base64" .env; then
  echo "🔑 APP_KEY generate ediliyor..."
  php artisan key:generate
fi

# Migration çalıştır
echo "🛢️ Migration çalıştırılıyor..."
php artisan migrate --force || true

# Cache optimize
echo "⚡ Cache optimize..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Permission fix
chown -R www-data:www-data /var/www/html

echo "✅ Laravel hazır!"

# Apache başlat
apache2-foreground