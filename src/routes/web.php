<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| JSQL Routes
|
*/

Route::post('/jsql/select', 'Jsql\LaravelPlugin\JsqlController@select')->name('jsql.select');
Route::post('/jsql/insert', 'Jsql\LaravelPlugin\JsqlController@insert')->name('jsql.insert');
Route::post('/jsql/update', 'Jsql\LaravelPlugin\JsqlController@update')->name('jsql.update');
Route::post('/jsql/delete', 'Jsql\LaravelPlugin\JsqlController@delete')->name('jsql.delete');


