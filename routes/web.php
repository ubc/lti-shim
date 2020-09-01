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

// enable login system but disable the registration page
Auth::routes(['register' => false]);

// shim Admin pages
Route::get('/', 'WelcomeController@index');
Route::get('/admin', 'HomeController@index')->name('admin');
Route::get('/account', 'HomeController@account')->name('account');
