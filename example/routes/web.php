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

Route::get('/', function () {
    return view('welcome');
});

Route::get('article/{slug}', ['as' => 'article', function ($slug) {
    return 'hello';
}]);

Route::get('tag/{tag}', ['as' => 'tag', function (App\Tag $tag) {
    return $tag;
}]);
