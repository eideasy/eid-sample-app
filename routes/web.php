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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sign-locally-sample', 'SampleViewController@signLocallySample');

Route::get('/add-signature-signed-file', 'SampleViewController@signAsiceFile');
Route::post('/add-signature-signed-file', 'SignPreparedDocumentController@startAddSignature');

Route::get('/sign-custom-file', 'SampleViewController@signCustomFile');
Route::post('/sign-custom-file', 'SignPreparedDocumentController@startSignCustomFile');

Route::get('/show-download-signed-file', 'SampleViewController@showDownloadSignedFile');
Route::get('/download-signed-file', 'SignPreparedDocumentController@downloadSignedFile');
