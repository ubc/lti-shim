## LTI Shim

## Development

### Develop in Docker

#### Initial setup:

```
git clone --recurse-submodules -b prototype1 https://github.com/ubc/lti-shim.git
cd lti-shim/
cp .env.example .env
cd laradock-lti-shim/
docker-compose up -d nginx postgres workspace adminer ltijs-demo-server
docker-compose exec -u laradock workspace bash
  workspace$ composer install
  workspace$ artisan key:generate
  workspace$ artisan migrate:refresh --seed
  workspace$ artisan passport:install
  workspace$ npm install
  workspace$ npm run dev
```

* The main app is accessible from http://localhost
  * See routes/web.php for valid locations
  * App log is located in `storage/logs/`, they are named by date. Note that the containers seems to be on UTC though.
  * Development admin user login (added as part of seeded data):
    * Email: admin@example.com
    * Password: password
* Workspace is a container for executing composer/artisan commands on your project. This means you don't need to have composer/artisan installed locally.
* Adminer provides a simple front-end to the database and is accessible at http://localhost:8081/
  * System: PostgreSQL
  * Server: postgres
  * Username: default
  * Password: secret
  * Database: default
* Ltijs Demo Server is used for development testing as a target tool.
  * OAuth client id: CLIENTID
  * LTI launch OIDC login URL: http://localhost:4000/login
  * LTI launch authorization response URL: http://localhost:4000/
  * LTI launch target (target_link_uri): http://localhost:4000/
  * JWKS url
    * inside Laradock: http://ltijs-demo-server:4000/keys
    * outside Laradock: http://localhost:4000/keys

After the initial setup, you can bring up the containers and access the workspace with just the docker-compose commands.

#### Database Setup

Database migration needs to be run manually whenever new migrations are added or when starting a fresh instance. These migrations needs to be run in the workspace container.

```
docker-compose exec -u laradock workspace bash
  workspace$ artisan migrate
```

Migrations can be rolled back in bulk (`artisan migrate:rollback`) or with the addition of the `--step=N` option for finer control, rolling back N number of migration files.

The database can be completely blown away and rebuilt from scratch using `artisan migrate:refresh`.

If you reset the database, you will also need to run Passport migrations in order for the API login to work again:
```
artisan passport:install
```

##### Seeded Data

Test data for development use can be seeded using `artisan db:seed`. This can be combined with rebuilding the database from scratch as `artisan migrate:refresh --seed`.

The current seeded data works with this Reference Implemention platform: https://lti-ri.imsglobal.org/platforms/643

It should direct a launch from that RI platform to the Ltijs demo server brought up locally with docker-compose.

#### UI Setup

Laravel has its own configuration built on top of webpack, which means some setup is needed to start development. We need to install all the UI packages and then call webpack to compile all the new assets:

```
npm install
npm run dev
```

Changes to UI files requires us to recompile assets. In order to do this automatically, leave this command running:

```
npm run watch
```

The watch command still requires you to hit refresh in the browser. This can be a chore for vue development, so we can enable hot-module-replacement where assets are automatically updated without having to hit refresh. The command is:

```
npm run hot
```

Laravel uses VueJS for JavaScript framework and Bootstrap 4 for CSS.

#### Run Tests

Make sure you're in workspace, run `phpunit` or `artisan test` to run all the tests.

```
docker-compose exec -u laradock workspace bash
  workspace$ phpunit
  workspace$ artisan test
```

You can limit the test you run with the `--filter` parameter, for example, to run only the NrpsTest:

```
 $ artisan test --filter NrpsTest
 $ phpunit --filter NrpsTest
```

`artisan test` provides a fancier output and will stop on the first test failure. It should also pass all parameters to the underlying `phpunit`. But the option to call `phpunit` directly might be valuable in some circumstances.

#### LTI Module

LTI processing code is located in `lti/`. Different LTI functionalities are split into different specs, so we've organized them in the same way in `lti/Specs/`. The core spec is in `lti/Specs/Launch/`. Taking the launch as an example, it's been divided up into the Tool side and the Platform side in the form of `ToolLaunch` and `PlatformLaunch`.

The initial launch we receive from Canvas is processed by `ToolLaunch` since we're acting as a tool. When we pass the launch to the target tool, it is processed by `PlatformLaunch` since we're acting as a platform.

##### Configuration

Some LTI configuration is not stored in the database but uses Laravel's built in configuration system located at `config/lti.php`.

* *iss* - the iss parameter can just be the app's url
* *platform_id* - the shim's own platform information is stored in the `platforms` table, we need the id to that row.
* *tool_id* - the shim's own tool information is stored in the `tools` table, we need the id to that row.

##### RSA Key Storage

The JSON Web Token (JWT) spec uses RSA public/private keys. Keys are all stored as in the JSON Web Key (JWK) format. There are two types of keys, one used for signatures and one used for encryption.

Signed JWT (JWS) are used on both platform and tool side for the `id_token` parameter. As such, our platform and tool sides each have their own set of public/private keys. This is stored in the `platform_keys` and `tool_keys` table.

Encrypted JWT (JWE) is used for session state (see below). The keys are stored in the `encryption_keys` table.

Note that the Reference Implementation uses PEM formatted keys, so you'll have to use a JWK/PEM converter.

Visiting [http://localhost/lti/keygen](http://localhost/lti/keygen) will output a public/private keypair in JWK format in the logs as debug messages.

##### Session State

Due to SameSite cookie enforcement coming into effect, we're trying to avoid using cookies for the LTI requests. This means we can't use stock Laravel sessions, as those use cookies to store session IDs. The alternative is to the various state parameters specified by LTI to store the session ID. When acting as a Tool, this is the `state` param. When acting as a Platform, this is the `lti_message_hint` param.

To preserve privacy and to protect against CSRF, the state string is an encrypted JWT (JWE). This does result in a rather long string, about 1000 characters. We will need to be careful that it doesn't get too long that it doesn't fit into a GET request. Not an issue for the POST only `state` param, but the spec does require that `lti_message_hint` be both GET and POST compatible.

##### Data Filters

Each implemented spec has their own set of filters. For example, the LTI launch filters are located in `lti/Specs/Launch/Filters`. Each launch filters implements the `FilterInterface` in `lti/Specs/Launch/Filters/FilterInterface.php`. The `filter()` method takes an array and a `LtiSession` model. The array needs to be filtered, removing/renaming key/values as necessary, and then returned. To apply a new filter, add it to the `$filters` list in `PlatformLaunch`.

#### Troubleshooting

* If you're not the first user in the system (i.e.: uid/gid is not 1000/1000). You will have to edit `laradock-lti-shim/.env` and  change WORKSPACE_PUID, WORKSPACE_PGID, PHP_FPM_PUID, and PHP_FPM_PGID to your uid/gid. This is to avoid permission issues with the files created inside docker containers. E.g.: if you `composer install` a library while in the workspace container, the files downloaded by composer will be corrected own by you and not some other user.

* If dependencies seem out of date, you might need to rebuild the relevant docker images while avoiding the docker cache (where the outdated dependencies are stored), so something like: `docker-compose build --no-cache nginx postgres workspace adminer php-fpm`

* If Laradock has issues after an update from upstream Laradock:
  * You probably need to merge new entries in `env-example` into `.env`
  * You probably need to rebuild the docker images. Just in case, you should tell docker to attempt to pull newer versions of the images too while building, something like: `docker-compose build --pull nginx postgres workspace adminer php-fpm`

## Deployment

It would be advisable to run `artisan key:generate` to generate a different `APP_KEY` from development.

Run `artisan config:cache` to combine all configuration files into a single file for faster loading. This shouldn't be used during development as configuration files can change.

Database credentials in `.env` should be replaced with production values.

UI assets needs to be compiled and minified, this is done with: `npm run production`

## Contributing

## License

The LTI-Shim project is open-source software licensed under the [AGPL-3.0](https://opensource.org/licenses/AGPL-3.0).
