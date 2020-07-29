<?php

use App\Http\Controllers\HomeController;
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

Route::get('/add-signature-signed-file', 'SampleViewController@signAsiceFile');
Route::post('/add-signature-signed-file', 'AddSignatureController@startAddingSignature');

Route::get('/sign-custom-file', 'SampleViewController@signCustomFile');
Route::post('/sign-custom-file', 'SignPreparedDocumentController@startSignCustomFile');

Route::get('/show-download-signed-file', 'SampleViewController@showDownloadSignedFile');
Route::get('/download-signed-file', 'SignPreparedDocumentController@downloadSignedFile');
