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

Route::middleware('auth:api-user')->get('/user', function (Request $request) {
    return $request->user();
});

Route::namespace("\App\Utilities")->group(function () {
    Route::group(['prefix' => 'v1'], function(){
        Route::group(['prefix' => 'app'], function() {
            Route::post('/save', 'AppUtility@create')->name('api.app.create')->middleware('auth:api-user');
            Route::post('/save_bulk', 'AppUtility@create_bulk')->name('api.app.create_bulk')->middleware('auth:api-user');
            Route::get('/get', 'AppUtility@get')->name('api.app.get')->middleware('auth:api-user');
            Route::get('/single', 'AppUtility@single')->name('api.app.single')->middleware('auth:api-user');
            Route::get('/group', 'AppUtility@group')->name('api.app.group')->middleware('auth:api-user');
            Route::post('/create_user', 'AppUtility@create_user')->name('api.app.create_user');
            Route::post('/login', 'AppUtility@login')->name('api.app.login');
            Route::match(['get', 'post'],'/update', 'AppUtility@update')->name('api.app.update')->middleware('auth:api-user');
            Route::post('/run_request', 'AppUtility@run_request')->name('api.app.run_request');
            Route::post('/toggle_status', 'AppUtility@toggle_status')->name('api.app.toggle_status')->middleware('auth:api-user');
            
            Route::get('/sum', 'AppUtility@sum')->name('api.app.sum')->middleware('auth:api-user');
            Route::get('/count', 'AppUtility@count')->name('api.app.count')->middleware('auth:api-user');
            Route::get('/multi', 'AppUtility@multi')->name('api.app.multi')->middleware('auth:api-user');

            Route::post('/generate_token', 'AppUtility@generate_token')->name('api.app.generate_token');

            Route::post('/validate_token', 'AppUtility@validate_token')->name('api.app.validate_token');
            Route::post('/reset_password', 'AppUtility@reset_password')->name('api.app.reset_password');
            Route::post('/validate_pin', 'AppUtility@validate_pin')->name('api.app.validate_pin');

            
             
        });
        
    });
});

Route::namespace("API")->group(function () {
    Route::group(['prefix' => 'v1'], function(){
        Route::group(['prefix' => 'app'], function() {
            Route::get('/get_items_by_due_date_constraint', 'TodoController@get_items_by_timer_constraint')->name('api.app.get_items_by_timer_constraint')->middleware('auth:api-user');
            Route::get('/mark_as_done', 'TodoController@mark_as_done')->name('api.app.mark_as_done')->middleware('auth:api-user');
            Route::post('/delete_todo', 'TodoController@delete_todo')->name('api.app.delete_todo')->middleware('auth:api-user');
            Route::get('/get_closed_items', 'TodoController@get_closed_items')->name('api.app.get_closed_items')->middleware('auth:api-user');
            Route::get('/get_done_pending_ratio', 'TodoController@get_done_pending_ratio')->name('api.app.get_done_pending_ratio')->middleware('auth:api-user');
            
            
        });
    });
});

Route::namespace("API")->group(function () {
    Route::group(['prefix' => 'v1'], function(){
        Route::group(['prefix' => 'app'], function() {
            Route::get('/get_fund_tracker_by_date', 'InternalController@get_fund_tracker_by_date')->name('api.get_fund_tracker_by_date')->middleware('auth:api-user');
            
        });
    });
});
