<?php

use UBC\LTI\Specs\Launch\ToolLaunch;

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

Route::get('/launch', 'LtiController@launch');
Route::namespace('LTI\Launch')->group(function() {
	Route::get('/lti/launch/tool/login', 'ToolLaunchController@login');
});
