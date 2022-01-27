<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JsSdkController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/js-sdk/authorize', [JsSdkController::class, 'authorizeApiCall']);
Route::post('/js-sdk/decrypt-user-data', [JsSdkController::class, 'decryptUserData']);

Route::post('/identity/start', 'EmbeddedIdentityController@startLogin');
Route::post('/identity/finish', 'EmbeddedIdentityController@finishLogin');

Route::post('/test/custom-cades-digest', 'TestController@customCadesDigest');
