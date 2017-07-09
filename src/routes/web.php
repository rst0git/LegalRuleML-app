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

Route::get('/', 'PagesController@index');

Route::get('doc', 'DocumentsController@index');
Route::get('doc/upload', 'DocumentsController@upload')->middleware('auth');;
Route::post('doc/upload', 'DocumentsController@store')->middleware('auth');;
Route::get('doc/show/{id}', 'DocumentsController@show');
Route::get('doc/{id}/download', 'DocumentsController@download');
Route::delete('doc/{id}/delete', 'DocumentsController@destroy')->middleware('auth');;

Route::get('search', 'SearchController@index');
Route::post('search', 'SearchController@search');

Auth::routes();

Route::get('/dashboard', 'DashboardController@index');
