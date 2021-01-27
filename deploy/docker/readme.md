Used for deployment on Kubernetes using Gitlab CI/CD. The Dockerfile and gitlab
configuration file is located in the source root. The resulting image is based
on the php-apache image and will require a separate postgres database. The
image is built with Laravel optimizations for production. However, for real
production use, it might be better to split this image into two images, one for
running php-fpm and one for running the webserver.

The docker-compose file here is meant as a reference for writing Helm Charts
and shows how to properly bring up the image built from the Dockerfile. Run
`docker-compose up` in this directory to bring up an instance of what the
deployed shim should look like:
* Shim: http://localhost:5000
* Adminer: http://localhost:5001 - Database frontend, for debug purposes

Note that the variables configured in Laravel's .env file are turned into environment variables passed into the container. The DB variables are used to wait for the database to come up before performing database migrations. The ADMIN variables are used to create an admin user if it doesn't already exist.
* DB_HOST
* DB_PORT
* ADMIN_NAME
* ADMIN_EMAIL
* ADMIN_PASSWORD
* APP_KEY - A new key should be generated for security reasons.
* APP_URL - This should reflect the deployed URL.
* SESSION_DRIVER - Needs to be set to 'cookie' for Sanctum auth to work
* SANCTUM_STATEFUL_DOMAINS - Needs to be set the deployed URL. Session cookies need to match this domain in order to be considered valid.

The custom-entrypoint.sh file is the entrypoint script for the final image that
runs database related setup.
