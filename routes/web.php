<?php

use Illuminate\Support\Facades\Route;
use App\Events\WebsocketEvent;
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

// Auth::routes();

Route::get('/', function () {
    // event(new WebsocketEvent('some data'));
    return view('layouts.app');
});

Route::get('/reports_preview', 'ReportsPreviewController@preview');



