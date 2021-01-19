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

Route::middleware('auth:sanctum')->namespace('API')->group(function() {
    Route::apiResource('user', 'UserController');

    Route::apiResource('platform-client', 'PlatformClientController');

    Route::apiResource('platform', 'PlatformController');
    Route::apiResource('platform.keys', 'PlatformKeyController');

    Route::apiResource('tool', 'ToolController');
    Route::apiResource('tool.keys', 'ToolKeyController');

    Route::get('help/config', 'HelpController@config');
});
