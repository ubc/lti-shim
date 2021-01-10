let mix = require('laravel-mix');
let path = require('path');
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.sourceMaps()
  .js('resources/js/app.js', 'public/js').vue()
  .js('resources/js/midway.js', 'public/js').vue()
  .sass('resources/sass/app.scss', 'public/css');

mix.options({
  hmrOptions: {
    host: '0.0.0.0',
    port: 8080
  }
})

mix.webpackConfig({
  resolve: {
    alias: {
      'icons': path.resolve(__dirname, 'node_modules/vue-material-design-icons')
    },
    extensions: [
      '.vue'
    ]
  }
});
