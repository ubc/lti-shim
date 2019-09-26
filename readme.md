## LTI Shim 

## Development

### Develop in Docker
```
git clone https://github.com/Laradock/laradock.git
git clone https://github.com/ubcgithub/lti-shim.git
cd laradock
cp env-example .env
vi .env # change APP_CODE_PATH_HOST to "../lti-shim/"
docker-compose up -d nginx mysql phpmyadmin redis workspace
```
The app is accessible from http://localhost

## Contributing

## License

The LTI-Shim project is open-source software licensed under the [AGPL-3.0](https://opensource.org/licenses/AGPL-3.0).
