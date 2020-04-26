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
