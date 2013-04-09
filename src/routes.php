<?php

// Get the base dir for Decoy
$dir = Config::get('decoy::dir');

// Add routing for the login screen
Route::any($dir, array(
	'uses' => Decoy_Auth::login_action(),
));