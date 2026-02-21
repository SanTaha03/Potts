FROM php:8.2-fpm

FROM php:8.2-fpm

# Arguments pour l'utilisateur (facultatif si on force www-data)
ARG user=www-data
ARG uid=1000

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libsqlite3-dev \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip

# 3. Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Set working directory
WORKDIR /var/www

# 5. Copy project files
COPY . .

# 6. Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 7. Install Node dependencies and build assets
RUN npm install && npm run build

# 8. Setup SQLite
# Crée le dossier database s'il n'existe pas et un fichier vide si nécessaire
RUN mkdir -p database && touch database/database.sqlite

# 9. Fix permissions
# SQLite a besoin que le dossier parent soit aussi inscriptible
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/database \
    && chmod 664 /var/www/database/database.sqlite

USER www-data
