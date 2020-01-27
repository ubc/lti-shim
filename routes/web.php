<?php

use UBC\LTI\Specs\Launch\ToolLaunch;

use Jose\Component\KeyManagement\JWKFactory;


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

#Route::get('/', function () {
#    return view('welcome');
#});

Route::get('/launch', 'LtiController@launch');
Route::namespace('LTI\Launch')->group(function() {
    Route::get('/lti/launch/tool/login', 'ToolLaunchController@login');
    Route::post('/lti/launch/tool/login', 'ToolLaunchController@login');
    // unlike login, only POST requests are allowed for the auth response
    Route::post('/lti/launch/tool/auth', 'ToolLaunchController@auth');
});

// sample code for generating a public/private key pair using the JWT Framework
Route::get('/lti/keygen', function() {
    $key = JWKFactory::createRSAKey(
        4096, // Size in bits of the key. We recommend at least 2048 bits.
        [
            'alg' => 'RS256',
            'use' => 'sig',
            'key_ops' => ['sign', 'verify'],
            'kty' => 'RSA'
        ]);
    Log::debug("Public Only");
    Log::debug(json_encode($key->toPublic(), JSON_PRETTY_PRINT));
    Log::debug("Public & Private");
    Log::debug(json_encode($key, JSON_PRETTY_PRINT));
});
