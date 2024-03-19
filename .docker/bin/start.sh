#!/bin/bash
set -e

# Function to wait for a service to be ready
wait_for_service() {
    local service="$1"
    local host="$2"
    local port="$3"

echo
    until nc -z "$host" "$port"; do
        echo "Waiting for $service to be ready..."
        sleep 2
    done
    echo "$service is ready!"
}

# Wait for the database to be ready
wait_for_service "MySQL" "mysql" "3306"

# Run Composer install
composer install

php bin/console lexik:jwt:generate-keypair --overwrite

# Set ownership
chown -R www-data:www-data var public

php bin/console doctrine:migrations:migrate

# Start PHP-FPM
exec php-fpm -F
