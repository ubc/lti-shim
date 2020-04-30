<?php

use Illuminate\Http\Request;

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

Route::namespace('API')->group(function() {
    Route::get('/user', 'UserController@index');
    Route::put('/user', 'UserController@store');
    Route::get('/user/{id}', 'UserController@show');
    Route::post('/user/{id}', 'UserController@update');
    Route::delete('/user/{id}', 'UserController@destroy');

    Route::apiResource('platform', 'PlatformController');
    Route::apiResource('platform.clients', 'PlatformClientController');
    Route::apiResource('platform.keys', 'PlatformKeyController');

    Route::apiResource('tool', 'ToolController');
    Route::apiResource('tool.keys', 'ToolKeyController');
});
