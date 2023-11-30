<?php

use App\Http\Controllers\CscApiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OidcClientController;
use App\Http\Controllers\SampleViewController;
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

Route::get('/', 'HomeController@getWelcome');

Route::get('/sign-locally-sample', 'SampleViewController@signLocallySample');
Route::get('/embedded-identification', 'SampleViewController@getEmbeddedIdentification');

Route::get('/login-widget', 'SampleViewController@loginWidget');

Route::get('/add-signature-signed-file', 'SampleViewController@signAsiceFile');
Route::post('/add-signature-signed-file', 'AddSignatureController@startAddingSignature');

Route::get('/sign-custom-file', 'SampleViewController@signCustomFile');
Route::post('/sign-custom-file', 'SignLocallyController@startSignCustomFile');

Route::get('/download-unsigned-file', 'SignLocallyController@downloadUnSignedFile');
Route::get('/show-download-signed-file', 'SampleViewController@showDownloadSignedFile');
Route::get('/download-signed-file', 'SignLocallyController@downloadSignedFile');

Route::post('/test/custom-cades-digest', 'TestController@customCadesDigest');

Route::get('/sign-via-csc-api', [SampleViewController::class, 'signViaCscApiView']);
Route::post('/sign-via-csc-api', [CscApiController::class, 'startCscApiSignature']);
Route::get('/csc-service-return', [CscApiController::class, 'credential']);
Route::get('/csc-signature', [CscApiController::class, 'signature']);
Route::get('/download-csc-api-signed-file', [CscApiController::class, 'downloadSignedFile']);

Route::group(['prefix' => 'oidc'], function () {
    Route::get('/start', [OidcClientController::class, 'startAuthentication'])->name('oidc.start');
    Route::get('/callback', [OidcClientController::class, 'returnCallback'])->name('oidc.callback');
});
