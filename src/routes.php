<?php

// Get the base dir for Decoy
$dir = Config::get('decoy::dir');

// Add all the account stuff.  Which is the login and the ACL gate stuff
Route::get($dir, array('as' => 'decoy', 'uses' => DecoyAuth::loginAction()));
Route::post($dir, 'Bkwld\Decoy\Controllers\Account@post');
Route::get($dir.'/account', array('as' => 'decoy\account', 'uses' => 'Bkwld\Decoy\Controllers\Account@index'));
Route::get($dir.'/logout', array('as' => 'decoy\logout', 'uses' => 'Bkwld\Decoy\Controllers\Account@logout'));
Route::get($dir.'/forgot', array('as' => 'decoy\forgot', 'uses' => 'Bkwld\Decoy\Controllers\Account@forgot'));
Route::post($dir.'/forgot', 'Bkwld\Decoy\Controllers\Account@postForgot');
Route::get($dir.'/reset/{code}', array('as' => 'decoy\reset', 'uses' => 'Bkwld\Decoy\Controllers\Account@reset'));
Route::post($dir.'/reset/{code}', 'Bkwld\Decoy\Controllers\Account@postReset');

// Testing
Route::get('/admin/news', 'Admin\NewsController@index');