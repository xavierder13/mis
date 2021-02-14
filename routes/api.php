<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication Route
Route::prefix('auth')->group(function(){
    Route::get('/init', [
        'uses' => 'API\AuthController@init',
        'as' => 'auth.init'
    ])->middleware('auth:api');

    Route::post('/login', [
        'uses' => 'API\AuthController@login',
        'as' => 'auth.login'
    ]);

    Route::post('/register', [
        'uses' => 'API/AuthController@register',
        'as' => 'auth.register'
    ]);

    Route::get('/logout', [
        'uses' => 'API\AuthController@logout',
        'as' => 'auth.logout'
    ])->middleware('auth:api');
});

// Projects/Dashboard Routes
Route::group(['prefix' => 'project', 'middleware' => ['auth:api']], function(){
    Route::get('/index', [
        'uses' => 'API\ProjectController@index',
        'as' => 'project.index',
    ]);

    Route::post('/store', [
        'uses' => 'API\ProjectController@store',
        'as' => 'project.store',
    ]);

    Route::get('/edit/{id}', [
        'uses' => 'API\ProjectController@edit',
        'as' => 'project.edit',
    ]);

    Route::post('/update/{id}', [
        'uses' => 'API\ProjectController@update',
        'as' => 'project.update',
    ]);

    Route::post('/delete', [
        'uses' => 'API\ProjectController@delete',
        'as' => 'project.delete',
    ]);

    Route::get('/get_ref_no', [
        'uses' => 'API\ProjectController@getRefNo',
        'as' => 'get_ref_no',
    ]);

});

// User Routes
Route::group(['prefix' => 'user', 'middleware' => ['auth:api']], function(){
    Route::get('/index', [
        'uses' => 'API\UserController@index',
        'as' => 'user.index',
    ]);

    Route::post('/store', [
        'uses' => 'API\UserController@store',
        'as' => 'user.store',
    ]);

    Route::get('/edit/{id}', [
        'uses' => 'API\UserController@edit',
        'as' => 'user.edit',
    ]);

    Route::post('/update/{id}', [
        'uses' => 'API\UserController@update',
        'as' => 'user.update',
    ]);

    Route::post('/delete', [
        'uses' => 'API\UserController@delete',
        'as' => 'user.delete',
    ]);

    Route::get('roles_permissions', [
        'uses' => 'API\UserController@userRolesPermissions',
        'as' => 'user.roles_permissions',
    ]);

});


// User Routes
Route::group(['prefix' => 'department', 'middleware' => ['auth:api']], function(){
    Route::get('/index', [
        'uses' => 'API\DepartmentController@index',
        'as' => 'department.index',
    ]);

    Route::post('/store', [
        'uses' => 'API\DepartmentController@store',
        'as' => 'department.store',
    ]);

    Route::get('/edit/{id}', [
        'uses' => 'API\DepartmentController@edit',
        'as' => 'department.edit',
    ]);

    Route::post('/update/{id}', [
        'uses' => 'API\DepartmentController@update',
        'as' => 'department.update',
    ]);

    Route::post('/delete', [
        'uses' => 'API\DepartmentController@delete',
        'as' => 'department.delete',
    ]);

});

// User Routes
Route::group(['prefix' => 'manager', 'middleware' => ['auth:api']], function(){
    Route::get('/index', [
        'uses' => 'API\ManagerController@index',
        'as' => 'manager.index',
    ]);

    Route::post('/store', [
        'uses' => 'API\ManagerController@store',
        'as' => 'manager.store',
    ]);

    Route::get('/edit/{id}', [
        'uses' => 'API\ManagerController@edit',
        'as' => 'manager.edit',
    ]);

    Route::post('/update/{id}', [
        'uses' => 'API\ManagerController@update',
        'as' => 'manager.update',
    ]);

    Route::post('/delete', [
        'uses' => 'API\ManagerController@delete',
        'as' => 'manager.delete',
    ]);


});

// Ref No. Settings Routes
Route::group(['prefix' => 'ref_no_setting', 'middleware' => ['auth:api']], function(){
    Route::get('/index', [
        'uses' => 'API\RefNoSettingController@index',
        'as' => 'ref_no_setting.index',
    ]);
    Route::post('/update/{id}', [
        'uses' => 'API\RefNoSettingController@update',
        'as' => 'ref_no_setting.index',
    ]);

});
