# Production Dockerfile for Symfony 7.3 + PHP 8.2 on Railway
# Multi-stage build for optimized image size

# Stage 1: Builder - Install dependencies and build assets
FROM php:8.2-fpm-alpine AS builder

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        intl \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        zip \
        opcache \
        gd \
        mbstring

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock symfony.lock ./

# Install PHP dependencies (production only)
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --apcu-autoloader \
    --no-scripts

# Copy application files
COPY . .

# Install Node dependencies and build assets
RUN npm ci --only=production && \
    npm run build || true

# Create minimal .env for build (Symfony needs it for cache:clear)
RUN echo "APP_ENV=prod" > .env && \
    echo "APP_DEBUG=0" >> .env

# Run composer scripts (assets:install, etc.)
RUN APP_ENV=prod APP_DEBUG=0 composer run-script --no-dev post-install-cmd

# Stage 2: Production - Minimal runtime image
FROM php:8.2-fpm-alpine

# Install runtime dependencies only (including build deps for PHP extensions)
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    icu-libs \
    oniguruma \
    postgresql-libs \
    nginx \
    supervisor \
    zlib-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        intl \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        zip \
        opcache \
        gd \
        mbstring

# Remove build dependencies to reduce image size
RUN apk del zlib-dev libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev icu-dev oniguruma-dev postgresql-dev

# Configure PHP for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'upload_max_filesize=10M'; \
    echo 'post_max_size=10M'; \
    echo 'memory_limit=256M'; \
    echo 'max_execution_time=300'; \
} > /usr/local/etc/php/conf.d/production.ini

# Copy application from builder
COPY --from=builder --chown=www-data:www-data /app /app

# Set working directory
WORKDIR /app

# Create required directories with proper permissions
RUN mkdir -p var/cache var/log public/uploads/images public/uploads/pdfs \
    public/uploads/profile_pictures public/uploads/auteur_images \
    public/uploads/review_images && \
    chown -R www-data:www-data var public/uploads && \
    chmod -R 755 var public/uploads

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/symfony.conf /etc/nginx/http.d/default.conf

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisord.conf

# Copy startup script
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Expose port (Railway will inject $PORT)
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php -r "if (file_get_contents('http://localhost:8080/') === false) exit(1);" || exit 1

# Start supervisor (runs nginx + php-fpm)
CMD ["/usr/local/bin/start.sh"]
