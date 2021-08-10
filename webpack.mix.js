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

mix
  .js('resources/js/admin/admin.js', 'public/js').vue()
  .js('resources/js/midway/midway.js', 'public/js').vue()
  .postCss('resources/css/app.css', 'public/css');

if (mix.inProduction()) {
  // generate a unique hash for filenames, prevent browsers are using old
  // cached assets
  mix.version();
}
else {
  // only need to generate source maps in dev
  mix.sourceMaps();
}

mix.options({
  hmrOptions: {
    host: '0.0.0.0',
    port: 8080
  }
})

mix.webpackConfig({
  resolve: {
    alias: {
      'icons': path.resolve(__dirname, 'node_modules/vue-material-design-icons'),
      '@admin': path.resolve(__dirname, 'resources/js/admin')
    },
    extensions: [
      '.vue'
    ]
  }
});
