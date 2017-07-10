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

Route::get('/', 'PagesController@index')->name('home');

Route::get('doc', 'DocumentsController@index')->name('doc');

Route::get('doc/upload', 'DocumentsController@upload')->name('doc_upload')
                                                      ->middleware('auth');

Route::post('doc/upload', 'DocumentsController@store')->name('doc_upload_post')
                                                      ->middleware('auth');

Route::get('doc/show/{title}', 'DocumentsController@show')->name('doc_show');

Route::get('doc/{id}/download', 'DocumentsController@download')->name('doc_download');

Route::delete('doc/{id}/delete', 'DocumentsController@destroy')->name('doc_delete')
                                                               ->middleware('auth');

Route::get('search', 'SearchController@index')->name('search');
Route::post('search', 'SearchController@search')->name('search_post');

Auth::routes();

Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
