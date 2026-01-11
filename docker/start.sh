#!/bin/sh
set -e

# Wait for database to be ready (Railway provides DATABASE_URL)
if [ -n "$DATABASE_URL" ]; then
    echo "Waiting for database connection..."
    until php -r "
        \$url = parse_url(getenv('DATABASE_URL'));
        \$host = \$url['host'] ?? 'localhost';
        \$port = \$url['port'] ?? 5432;
        \$socket = @fsockopen(\$host, \$port, \$errno, \$errstr, 2);
        if (\$socket) { fclose(\$socket); exit(0); } else { exit(1); }
    "; do
        echo "Database is unavailable - sleeping"
        sleep 2
    done
    echo "Database is ready!"
fi

# Set production environment
export APP_ENV=${APP_ENV:-prod}
export APP_DEBUG=${APP_DEBUG:-0}

# Railway injects PORT variable - use it or default to 8080
export PORT=${PORT:-8080}
echo "Starting on port $PORT"

# Update nginx configuration to use PORT
sed -i "s/listen 8080;/listen $PORT;/" /etc/nginx/http.d/default.conf

# Warm up cache (only if cache directory exists)
if [ -d "var/cache" ]; then
    echo "Warming up Symfony cache..."
    php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true
fi

# Run database migrations (optional - uncomment if you want automatic migrations)
# php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>/dev/null || true

# Start supervisor (runs nginx + php-fpm)
exec /usr/bin/supervisord -c /etc/supervisord.conf
