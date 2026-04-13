FROM php:8.3-fpm-alpine

# Update package manager and install system dependencies
RUN apk update && apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    sqlite-dev

# Install Redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install pdo_sqlite pdo_mysql mbstring exif pcntl bcmath gd xml

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application code
COPY . .

# Set permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app/storage \
    && chmod -R 755 /app/bootstrap/cache

# Create .env file if it doesn't exist
RUN cp .env.example .env || echo "APP_NAME=Laravel\nAPP_ENV=production\nAPP_KEY=\nAPP_DEBUG=false\nAPP_URL=http://localhost\n\nLOG_CHANNEL=stack\nLOG_LEVEL=error\n\nDB_CONNECTION=sqlite\nDB_DATABASE=/app/database/database.sqlite\n\nCACHE_STORE=file\nQUEUE_CONNECTION=sync\n\nSESSION_DRIVER=file\n\n" > .env

# Generate application key if not set
RUN php artisan key:generate || true

# Create database file
RUN touch database/database.sqlite

# Run database migrations
RUN php artisan migrate --force || true

# Build assets (if using Vite)
RUN npm install && npm run build || true

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]