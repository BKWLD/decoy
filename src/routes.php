<?php

// Get the base dir for Decoy
$dir = Config::get('decoy::dir');

// Add all the account stuff.  Which is the login and the ACL gate stuff
Route::get($dir, Decoy_Auth::login_action());
Route::post($dir, 'Bkwld\Decoy\Controllers\Account@post');
Route::get($dir.'/account', 'Bkwld\Decoy\Controllers\Account@index');
Route::get($dir.'/logout', 'Bkwld\Decoy\Controllers\Account@logout');
Route::get($dir.'/forgot', 'Bkwld\Decoy\Controllers\Account@forgot');
Route::post($dir.'/forgot', 'Bkwld\Decoy\Controllers\Account@forget');
Route::get($dir.'/reset', 'Bkwld\Decoy\Controllers\Account@reset');

// Testing
Route::get('/admin/news', 'Admin\NewsController@index');