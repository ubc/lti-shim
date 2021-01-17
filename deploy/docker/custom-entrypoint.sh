#!/bin/bash
set -e

echo 'Waiting for database to be available...'
wait-for-it -t 60 "${DB_HOST}:${DB_PORT}"
echo 'Regenerate config in case .env changed...'
php artisan config:cache
echo 'Running database migrations...'
php artisan migrate
echo 'Creating admin user if not exists...'
php artisan user:add "${ADMIN_NAME}" "${ADMIN_EMAIL}" "${ADMIN_PASSWORD}"
echo 'Seeding LTI info...'
php artisan lti:seed
echo 'Done setting up!'

exec "$@"
