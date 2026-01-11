#!/bin/sh
set -e

# Create supervisor and nginx directories if they don't exist
mkdir -p /var/log/supervisor /var/run /var/log/nginx

# Wait for database to be ready (Railway provides DATABASE_URL or DATABASE_PUBLIC_URL)
# Use DATABASE_PUBLIC_URL if available (for Railway), otherwise fallback to DATABASE_URL
DB_URL="${DATABASE_PUBLIC_URL:-$DATABASE_URL}"

if [ -n "$DB_URL" ]; then
    echo "Waiting for database connection..."
    echo "Database URL format: ${DB_URL:0:60}..."
    until php -r "
        \$url = parse_url(getenv('DATABASE_PUBLIC_URL') ?: getenv('DATABASE_URL'));
        \$host = \$url['host'] ?? 'localhost';
        \$port = \$url['port'] ?? 5432;
        echo \"Trying to connect to \$host:\$port...\n\";
        \$socket = @fsockopen(\$host, \$port, \$errno, \$errstr, 5);
        if (\$socket) { fclose(\$socket); echo \"Connection successful!\n\"; exit(0); } else { echo \"Connection failed: \$errstr (\$errno)\n\"; exit(1); }
    "; do
        echo "Database is unavailable - sleeping"
        sleep 2
    done
    echo "Database is ready!"
else
    echo "WARNING: Neither DATABASE_URL nor DATABASE_PUBLIC_URL is set!"
fi

# Set production environment
export APP_ENV=${APP_ENV:-prod}
export APP_DEBUG=${APP_DEBUG:-0}

# Railway injects PORT variable - use it or default to 8080
export PORT=${PORT:-8080}
echo "Starting on port $PORT"

# Update nginx configuration to use PORT (listen on 0.0.0.0 for Railway)
sed -i "s/listen 0.0.0.0:8080;/listen 0.0.0.0:$PORT;/" /etc/nginx/http.d/default.conf

# Warm up cache (only if cache directory exists)
if [ -d "var/cache" ]; then
    echo "Warming up Symfony cache..."
    php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true
fi

# Run database migrations automatically on startup
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod --no-debug || echo "Migrations failed or already up to date"

# Run fixtures once if RUN_FIXTURES environment variable is set to "true"
if [ "$RUN_FIXTURES" = "true" ]; then
    echo "Loading fixtures..."
    php bin/console app:load-fixtures --purge --env=prod --no-interaction --no-debug || echo "Fixtures failed or already loaded"
    echo "Fixtures loaded. Remove RUN_FIXTURES variable from Railway to prevent re-running."
fi

# Start supervisor (runs nginx + php-fpm)
exec /usr/bin/supervisord -c /etc/supervisord.conf
