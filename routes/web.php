<?php

/*
|--------------------------------------------------------------------------
| Web Routes for Tracy
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => '/mxframe', 'as' => 'mxframe::'], function () {
    Route::group(['prefix' => '/tracy', 'as' => 'tracy::'], function () {
        Route::any('/bar', ['as' => 'bar', 'uses' => 'TracyController@bar']);
    });
});
