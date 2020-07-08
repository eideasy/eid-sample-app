<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('/signatures/create-container-for-signing', 'SignLocallyController@createContainerForSigning');
Route::post('/signatures/finalize-external-signature', 'SignLocallyController@finalizeSignature');

Route::post('/identity/smart-id/start', 'EmbeddedIdentityController@startSmartidLogin');
Route::post('/identity/smart-id/finish', 'EmbeddedIdentityController@finishSmartidLogin');

Route::post('/identity/mobile-id/start', 'EmbeddedIdentityController@startMobileidLogin');
Route::post('/identity/mobile-id/finish', 'EmbeddedIdentityController@finishMobileidLogin');

Route::post('/identity/id-card/finish', 'EmbeddedIdentityController@finishIdcardLogin');
