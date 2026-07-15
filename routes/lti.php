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
        // MIDWAY - perform operations on the shim
        Route::post('/launch/midway', 'LaunchController@midway')
             ->name('midway');
        // LAUNCH
        Route::match(
            ['get', 'post'],
            '/launch/login/tool/{toolId}',
            'LaunchController@login'
        )->name('login');
        Route::match(
            ['get', 'post'],
            '/launch/auth',
            'LaunchController@auth'
        )->name('auth');
        Route::post('/launch/redirect', 'LaunchController@redirect')
             ->name('redirect');
        // DEEP LINK
        Route::post('/launch/return/{deepLink}', 'LaunchController@return')
             ->name('dl.return');
        Route::match(
            ['get', 'post'],
            '/launch/login/tool/{toolId}/dlci/{deepLinkContentItemId}',
            'LaunchController@login'
        )->name('dl.contentItemLaunch');
    });
    // LTI Core Spec
    Route::namespace('Core')->name('core.')->group(function() {
        Route::get('/core/platform/return/{returnUrl}/{token}',
            'ReturnUrlController@getReturnUrl')->name('return');

    });
    // LTI Security Spec
    Route::namespace('Security')->name('security.')->group(function() {
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
