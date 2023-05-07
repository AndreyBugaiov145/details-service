const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js').vue()
    .js('resources/js/page/setting/main.js', 'public/js/page/setting').vue()
    .js('resources/js/page/home/main.js', 'public/js/page/home').vue()
    /*.js('resources/js/js.js', 'public/js')*/
    .sass('resources/sass/app.scss', 'public/css').options({
    processCssUrls: false
});
