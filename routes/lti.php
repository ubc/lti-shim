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

// NOTE: All these paths are prefix with /lti/
Route::namespace('LTI')->name('lti.')->group(function() {
    // a list of public keys that can be used to verify our signed JWTs
    Route::name('jwks.')->group(function() {
        Route::get('/platform/jwks', 'JWKSController@platformPublicKeys')
               ->name('platform');
        Route::get('/tool/jwks', 'JWKSController@toolPublicKeys')
               ->name('tool');
        //Route::get('/keygen', 'JWKSController@keygen'); // generates jwk in log
    });
    // LTI Launch (many specs, such as Core)
    Route::namespace('Launch')->name('launch.')->group(function() {
        // TOOL
        Route::name('tool.')->group(function() {
            Route::match(
                ['get', 'post'],
                '/launch/tool/login',
                'ToolLaunchController@login'
            )->name('login');
            // unlike login, only POST is allowed for the auth response
            Route::post('/launch/tool/auth', 'ToolLaunchController@auth')
                   ->name('authResp');
        });
        // MIDWAY - transfer station from the tool side to the platform side
        Route::get('/launch/midway/arrival', 'MidwayController@arrival');
        Route::post('/launch/midway/departure', 'MidwayController@departure');
        // PLATFORM
        Route::name('platform.')->group(function() {
            Route::match(
                ['get', 'post'],
                '/launch/platform/login',
                'PlatformLaunchController@login'
            )->name('login');
            Route::match(
                ['get', 'post'],
                '/launch/platform/auth',
                'PlatformLaunchController@auth'
            )->name('authReq');
        });
    });
    // LTI Core Spec
    Route::namespace('Core')->name('core.')->group(function() {
        Route::get('/core/platform/return/{returnUrl}/{token}',
            'ReturnUrlController@getReturnUrl')->name('return');

    });
    // LTI Security Spec
    Route::namespace('Security')->group(function() {
        Route::post(
            '/security/platform/token',
            'AccessTokenController@platformToken'
        )->name('token');
    });
    // LTI Names and Role Provisioning Services
    Route::namespace('Nrps')->group(function() {
        Route::get('/nrps/platform/{nrps}', 'NrpsController@nrps')
            ->name('nrps');
    });
    // LTI Assignment and Grade Service
    Route::namespace('Ags')->name('ags.')->group(function() {
        // lineitems url
        Route::get('/ags/platform/{ags}', 'AgsController@getLineitems')
            ->name('lineitems');
        Route::post('/ags/platform/{ags}', 'AgsController@postLineitems');
        // lineitem url
        Route::get('/ags/platform/{ags}/lineitem/{lineitem}',
                   'AgsController@getLineitem')
            ->name('lineitem');
        Route::put('/ags/platform/{ags}/lineitem/{lineitem}',
                   'AgsController@putLineitem');
        Route::delete('/ags/platform/{ags}/lineitem/{lineitem}',
                   'AgsController@deleteLineitem');
        // results
        Route::get('/ags/platform/{ags}/lineitem/{lineitem}/results',
                   'AgsController@getResults')
            ->name('results');
        Route::get('/ags/platform/{ags}/lineitem/{lineitem}/results/{result}',
                   'AgsController@getResult')
            ->name('result');
        // score
        Route::post('/ags/platform/{ags}/lineitem/{lineitem}/scores',
                    'AgsController@postScore')
            ->name('scores');
    });
});
