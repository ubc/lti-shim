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
    Route::delete('/user/{id}', 'UserController@destroy');
});
