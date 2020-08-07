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

Route::namespace('LTI')->group(function() {
    // a list of public keys that can be used to verify our signed JWTs
    Route::get(config('lti.platform_jwks_path'),
        'JWKSController@platformPublicKeys');
    Route::get(config('lti.tool_jwks_path'), 'JWKSController@toolPublicKeys');
    Route::get('/lti/keygen', 'JWKSController@keygen'); // TODO dev only, rm later
    // LTI Launch (Core Spec)
    Route::namespace('Launch')->group(function() {
        // TOOL
        Route::match(
            ['get', 'post'],
            config('lti.tool_launch_login_path'),
            'ToolLaunchController@login'
        );
        // unlike login, only POST requests are allowed for the auth response
        Route::post(
            config('lti.tool_launch_auth_resp_path'),
            'ToolLaunchController@auth'
        );
        // MIDWAY - transfer station from the tool side to the platform side
        Route::get('/lti/launch/midway/arrival', 'MidwayController@arrival');
        Route::post('/lti/launch/midway/departure', 'MidwayController@departure');
        // PLATFORM
        Route::match(
            ['get', 'post'],
            config('lti.platform_launch_login_path'),
            'PlatformLaunchController@login'
        );
        Route::match(
            ['get', 'post'],
            config('lti.platform_launch_auth_req_path'),
            'PlatformLaunchController@auth'
        );
    });
    // LTI Security Spec
    Route::namespace('Security')->group(function() {
        Route::post(
            config('lti.platform_security_token_path'),
            'AccessTokenController@platformToken'
        );
    });
    // LTI Names and Role Provisioning Services
    Route::namespace('Nrps')->group(function() {
        Route::get(config('lti.platform_nrps_path'), 'NrpsController@nrps')
            ->name('nrps');
    });
});

// enable login system but disable the registration page
Auth::routes(['register' => false]);

Route::get('/', 'WelcomeController@index');
Route::get('/admin', 'HomeController@index')->name('admin');
Route::get('/account', 'HomeController@account')->name('account');
