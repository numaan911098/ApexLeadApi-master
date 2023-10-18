const mix = require('laravel-mix');

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

// Form Generator
mix.js('resources/leadgenform/js/leadgenform-key.js', 'public/js').vue();

// Lead Proof
mix.js('storage/app/leadgenproof/proof.js', 'public/js')

// External Checkout
mix.js('storage/app/external-checkout/scripts.js', 'public/js/external-checkout.js')

// App
mix.js('resources/assets/js/app.js', 'public/js').vue();
