## LTI Shim 

## Development

### Develop in Docker

#### Initial setup:

```
git clone --recurse-submodules -b prototype1 git@github.com:/ubc/lti-shim.git
cd lti-shim/
cp .env-example .env
cd laradock-lti-shim/
docker-compose up -d nginx postgres workspace adminer
docker-compose exec -u laradock workspace bash
  workspace$ composer install
  workspace$ artisan key:generate
```

* The main app is accessible from http://localhost
  * App log is located in `storage/logs/`, they are named by date. Note that the containers seems to be on UTC though.
* Workspace is a container for executing composer/artisan commands on your project. This means you don't need to have composer/artisan installed locally.
* Adminer provides a simple front-end to the database and is accessible at http://localhost:8080/
  * Database Type: postgres
  * Database Name: default
  * Username: default
  * Password: secret

After the initial setup, you can bring up the containers and access the workspace with just the docker-compose commands.

#### Database Setup

Database migration needs to be run manually whenever new migrations are added or when starting a fresh instance. These migrations needs to be run in the workspace container.

```
docker-compose exec -u laradock workspace bash
  workspace$ artisan migrate
```

Migrations can be rolled back in bulk (`artisan migrate:rollback`) or with the addition of the `--step=N` option for finer control, rolling back N number of migration files.

The database can be completely blown away and rebuilt from scratch using `artisan migrate:refresh`.

Test data for development use can be seeded using `artisan db:seed`. This can be combined with rebuilding the database from scratch as `artisan migrate:refresh --seed`.


#### Troubleshooting

* If you're not the first user in the system (i.e.: uid/gid is not 1000/1000). You will have to edit `laradock-lti-shim/.env` and  change WORKSPACE_PUID, WORKSPACE_PGID, PHP_FPM_PUID, and PHP_FPM_PGID to your uid/gid. This is to avoid permission issues with the files created inside docker containers. E.g.: if you `composer install` a library while in the workspace container, the files downloaded by composer will be corrected own by you and not some other user.

* If dependencies seem out of date, `docker-compose build --no-cache nginx postgres workspace adminer`

## Deployment

It would be advisable to run `artisan key:generate` to generate a different `APP_KEY` from development.

Run `artisan config:cache` to combine all configuration files into a single file for faster loading. This shouldn't be used during development as configuration files can change.

Database credentials in `.env` should be replaced with production values.

## Contributing

## License

The LTI-Shim project is open-source software licensed under the [AGPL-3.0](https://opensource.org/licenses/AGPL-3.0).
