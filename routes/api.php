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

    Route::post('/programmer_reports', [
        'uses' => 'API\ProjectController@programmer_reports',
        'as' => 'project.programmer_reports',
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

    Route::post('/update_status', [
        'uses' => 'API\ProjectController@update_status',
        'as' => 'project.update_status',
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

// Projects Logs Routes
Route::group(['prefix' => 'project_log', 'middleware' => ['auth:api']], function(){
    Route::get('/index/{id}', [
        'uses' => 'API\ProjectLogController@index',
        'as' => 'project_log.index',
    ]);

    Route::post('/store', [
        'uses' => 'API\ProjectLogController@store',
        'as' => 'project_log.store',
    ]);

    Route::get('/edit/{id}', [
        'uses' => 'API\ProjectLogController@edit',
        'as' => 'project_log.edit',
    ]);

    Route::post('/update/{id}', [
        'uses' => 'API\ProjectLogController@update',
        'as' => 'project_log.update',
    ]);

    Route::post('/delete', [
        'uses' => 'API\ProjectLogController@delete',
        'as' => 'project_log.delete',
    ]);

    Route::post('/project_turnover', [
        'uses' => 'API\ProjectLogController@project_turnover',
        'as' => 'project_log.project_turnover',
    ]);

    Route::get('/get_latest_log/{id}', [
        'uses' => 'API\ProjectLogController@get_latest_log',
        'as' => 'project_log.get_latest_log',
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

// Department Routes
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

// Manager Routes
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

// Holiday Routes
Route::group(['prefix' => 'holiday', 'middleware' => ['auth:api']], function(){
    Route::get('/index', [
        'uses' => 'API\HolidayController@index',
        'as' => 'holiday.index',
    ]);

    Route::post('/store', [
        'uses' => 'API\HolidayController@store',
        'as' => 'holiday.store',
    ]);

    Route::get('/edit/{id}', [
        'uses' => 'API\HolidayController@edit',
        'as' => 'holiday.edit',
    ]);

    Route::post('/update/{id}', [
        'uses' => 'API\HolidayController@update',
        'as' => 'holiday.update',
    ]);

    Route::post('/delete', [
        'uses' => 'API\HolidayController@delete',
        'as' => 'holiday.delete',
    ]);
});

//Permissions
Route::group(['prefix' => 'permission', 'middleware' => ['auth:api']], function(){
    Route::get('/index', [
        'uses' => 'API\PermissionController@index',
        'as' => 'permission.index',
    ]);
    Route::get('/create', [
        'uses' => 'API\PermissionController@create',
        'as' => 'permission.create',
    ]);
    Route::post('/store', [
        'uses' => 'API\PermissionController@store',
        'as' => 'permission.store',
    ]);
    Route::get('/permissions', [
        'uses' => 'API\PermissionController@getpermissionrecord',
        'as' => 'getpermissionrecord',
    ]);
    Route::post('/edit', [
        'uses' => 'API\PermissionController@edit',
        'as' => 'permission.edit',
    ]);
    Route::post('/update/{id}', [
        'uses' => 'API\PermissionController@update',
        'as' => 'permission.update',
    ]);
    Route::post('/delete', [
        'uses' => 'API\PermissionController@delete',
        'as' => 'permission.delete',
    ]);

});

//Roles
Route::group(['prefix' => 'role', 'middleware' => ['auth:api']], function(){
    Route::get('/index', [
        'uses' => 'API\RoleController@index',
        'as' => 'role.index',
    ]);
    Route::get('/create', [
        'uses' => 'API\RoleController@create',
        'as' => 'role.create',
    ]);
    Route::post('/store', [
        'uses' => 'API\RoleController@store',
        'as' => 'role.store',
    ]);
    Route::get('/roles', [
        'uses' => 'API\RoleController@getrolerecord',
        'as' => 'getrolerecord',
    ]);
    Route::post('/edit', [
        'uses' => 'API\RoleController@edit',
        'as' => 'role.edit',
    ]);
    Route::post('/update/{id}', [
        'uses' => 'API\RoleController@update',
        'as' => 'role.update',
    ]);
    Route::post('/delete', [
        'uses' => 'API\RoleController@delete',
        'as' => 'role.delete',
    ]);

});

