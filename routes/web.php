<?php

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

Route::get('/', 'HomeController@home');

Route::namespace('LTI')->group(function() {
    // a list of public keys that can be used to verify our signed JWTs
    Route::get('/lti/platform/jwks', 'JWKSController@platformPublicKeys');
    Route::get('/lti/tool/jwks', 'JWKSController@toolPublicKeys');
    Route::get('/lti/keygen', 'JWKSController@keygen'); // TODO dev only, rm later
    Route::namespace('Launch')->group(function() {
        // TOOL
        Route::match(['get', 'post'], '/lti/launch/tool/login',
            'ToolLaunchController@login');
        // unlike login, only POST requests are allowed for the auth response
        Route::post('/lti/launch/tool/auth', 'ToolLaunchController@auth');
        // MIDWAY - transfer station from the tool side to the platform side
        Route::get('/lti/launch/midway/arrival', 'MidwayController@arrival');
        Route::post('/lti/launch/midway/departure', 'MidwayController@departure');
        // PLATFORM
        Route::match(['get', 'post'], '/lti/launch/platform/login',
            'PlatformLaunchController@login');
        Route::match(['get', 'post'], '/lti/launch/platform/auth',
            'PlatformLaunchController@auth');
    });
});
