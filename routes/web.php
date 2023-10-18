<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Facades\App\Services\Util;

$formsDomain = parse_url(Util::config('leadgen.forms_domain'), PHP_URL_HOST);
$pagesDomain = parse_url(Util::config('leadgen.pages_domain'), PHP_URL_HOST);
$proofsDomain = parse_url(Util::config('leadgen.proofs_domain'), PHP_URL_HOST);
$scriptsDomain = parse_url(Util::config('leadgen.scripts_domain'), PHP_URL_HOST);
$apiDomain =  parse_url(Util::config('leadgen.api_url'), PHP_URL_HOST);

Route::domain($formsDomain)->group(function () {
    // Form Public Scripts
    Route::get('js/lf.min.js/{key}', 'JsController@leadgenFormByKey');
    Route::get('js/lf-lib.min.js/{key}', 'JsController@leadgenFormLibJsByKey');
    Route::get('js/handler.js/{key}', 'JsController@iframeHandler');
    // Form Public Styles.
    Route::get('css/lf.min.css', 'CssController@leadgenForm');
    // Form Generator Preview
    Route::get('preview/forms/{form}/variants/{variant}', 'FormViewController@preview')
        ->name('leadgenform.preview');
    // Form Generator Publish
    Route::get('{key}/{method?}', 'FormViewController@publish')
        ->name('leadgenform.publish');
});

Route::domain($pagesDomain)->group(function () {
    Route::get('/{slug}', 'LandingPageController@showBySlug')->name('leadgenpage');
});

Route::domain($proofsDomain)->group(function () {
    Route::get('/{key}', 'LeadProofController@proof')->name('leadgenproof');
});

Route::domain($scriptsDomain)->group(function () {
    Route::get('/external-checkout', 'ExternalCheckoutController@scripts');
    Route::get('/external-checkout/{key}', 'ExternalCheckoutController@externalcheckout');
});

Route::domain($apiDomain)->group(function () {
    Route::get('/', 'HomeController@index');

    //Auth::Routes();

    Route::get('js/lf.min.js/{form}', 'JsController@leadgenForm');
    Route::get('js/lf-lib.min.js/{form}', 'JsController@leadgenFormLibJs');
    Route::get('js/lf-form.min.js/{form}', 'JsController@formJs');

    Route::get('css/lf.min.css', 'CssController@leadgenForm');
});
