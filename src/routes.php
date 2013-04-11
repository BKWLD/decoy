<?php

// Get the base dir for Decoy
$dir = Config::get('decoy::dir');

// Add all the account stuff.  Which is the login and the ACL gate stuff
Route::get($dir, array('as' => 'decoy', 'uses' => DecoyAuth::loginAction()));
Route::post($dir, 'Bkwld\Decoy\Controllers\Account@post');
Route::get($dir.'/account', array('as' => 'decoy\account', 'uses' => 'Bkwld\Decoy\Controllers\Account@index'));
Route::get($dir.'/logout', array('as' => 'decoy\account@logout', 'uses' => 'Bkwld\Decoy\Controllers\Account@logout'));
Route::get($dir.'/forgot', array('as' => 'decoy\account@forgot', 'uses' => 'Bkwld\Decoy\Controllers\Account@forgot'));
Route::post($dir.'/forgot', 'Bkwld\Decoy\Controllers\Account@postForgot');
Route::get($dir.'/reset/{code}', array('as' => 'decoy\account@reset', 'uses' => 'Bkwld\Decoy\Controllers\Account@reset'));
Route::post($dir.'/reset/{code}', 'Bkwld\Decoy\Controllers\Account@postReset');

// Wildcarded resourceful routing
// Route::get($dir.'/{controller}', 'Bkwld\Decoy\Router@index');
Route::get($dir.'/(.*)', function() {
	die('butt tiimss');
});

// Wildcarded routing for admin controllers.  This listens to all 404s
// and checks if the route looks like a controller route.  Then it finds
// the controller portion of the route and detects if it exists.  Then it
// checks for the 
App::missing(function($exception) use ($dir) {
	$router = new Bkwld\Decoy\Router($dir, Request::getMethod(), Request::path());
	$response = $router->detectAndExecute();
	if (is_a($response, 'Symfony\Component\HttpFoundation\Response')) return $response;
});

// Testing
// Route::get('/admin/news', 'Admin\NewsController@index');