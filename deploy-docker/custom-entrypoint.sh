#!/bin/bash
set -e

echo 'Waiting for database to be available...'
wait-for-it -t 60 "${DB_HOST}:${DB_PORT}"
echo 'Running database migrations...'
php artisan migrate
echo 'Generating OAuth keys...'
# Generates RSA keys and clients for OAuth. Note that while it'll refrain from
# overwriting existing keys, it'll create new clients even if duplicated. This
# is a pain where we'll end up with a new client every time the container
# starts.
# TODO: not a priority, but would be nice not to have this duplication.
php artisan passport:install
echo 'Creating admin user if not exists...'
php artisan user:add "${ADMIN_NAME}" "${ADMIN_EMAIL}" "${ADMIN_PASSWORD}"
echo 'Seeding LTI info...'
php artisan lti:seed
echo 'Done setting up!'

exec "$@"
