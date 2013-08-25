<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

// Generic site content
Router::register(array('GET', 'POST'), 
	"(:bundle)/content", 
	array('uses' => 'decoy::content@index', 'as' => 'decoy::content'));

// View worker status
Route::get('(:bundle)/workers', array('uses' => 'decoy::workers@index', 'as' => 'decoy::workers'));
Route::get('(:bundle)/workers/tail/(:any).txt', array('uses' => 'decoy::workers@tail', 'as' => 'decoy::workers@tail'));
