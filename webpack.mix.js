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

mix.js(['resources/js/views/authenticate/index'], 'public/global/assets/js/views/authenticate.min.js');
mix.js(['resources/js/views/portal/index'], 'public/global/assets/js/views/portal.min.js');
mix.js(['resources/js/views/admin/index'], 'public/global/assets/js/views/admin.min.js');
mix.js(['resources/js/views/embed-calculator/index'], 'public/global/assets/js/views/embed-calculator.min.js');