## LTI Shim 

## Development

### Develop in Docker
```
git clone https://github.com/Laradock/laradock.git
git clone https://github.com/ubc-lthub/lti-shim.git
echo 'APP_KEY=' > lti-shim/.env # This is the Laravel environment
cd laradock
cp env-example .env
vi .env # This is the Laradock environment
  # change APP_CODE_PATH_HOST to "../lti-shim/"
  # if your uid/gid is not 1000/1000, change WORKSPACE_PUID, WORKSPACE_PGID, PHP_FPM_PUID, and PHP_FPM_PGID
docker-compose up -d nginx mysql phpmyadmin redis workspace
docker-compose exec -u laradock workspace bash
  workspace$ composer install
  workspace$ artisan key:generate
  workspace$ artisan config:cache
```
The app is accessible from http://localhost

## Contributing

## License

The LTI-Shim project is open-source software licensed under the [AGPL-3.0](https://opensource.org/licenses/AGPL-3.0).
