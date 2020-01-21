<?php

use UBC\LTI\Core\Launch\ToolLaunch;

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
Route::get('/test', function() {
	$launch = new ToolLaunch();
	return $launch->test();
});
