<?php

use Illuminate\Support\Facades\Route;
use App\Events\WebsocketEvent;
use Symfony\Component\Console\Output\StreamOutput;
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

Route::get('run_serve', function () {
    $artisan = Artisan::call('serve --host="192.168.1.48" --port=1515');
});

Route::get('run_websocket_serve', function () {
    Artisan::call('websockets:serve');
});

Route::get('/reports_preview', 'ReportsPreviewController@preview');



