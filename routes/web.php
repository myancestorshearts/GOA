<?php

use Illuminate\Support\Facades\Route;

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

// migrations
Route::get('/migrate', 'ViewController@doMigrate');
Route::post('/migrate', 'ViewController@doMigrate');

// user portal
Route::get('/portal', 'ViewController@showPortal');
Route::get('/portal/{path}', 'ViewController@showPortal')->where('path', '.*');

// admin portal
Route::get('/admin', 'ViewController@showAdmin');
Route::get('/admin/{path}', 'ViewController@showAdmin')->where('path', '.*');

// embed caclulator
Route::get('/embed-calculator', 'ViewController@showEmbedCalculator');

// authenticate
Route::get('/', 'ViewController@showAuthenticate');
Route::get('/register', 'ViewController@showAuthenticate');
Route::get('/forgot', 'ViewController@showAuthenticate');
Route::get('/set', 'ViewController@showAuthenticate');
Route::get('/verify', 'ViewController@showAuthenticate');
