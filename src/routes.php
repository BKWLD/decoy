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

// Show all the task methods.  This option isn't typically isn't made
// available in the nav.
Route::get('(:bundle)/tasks', array('uses' => 'decoy::tasks@index', 'as' => 'decoy::tasks'));
Route::post('(:bundle)/tasks/(:any)/(:any)', array('uses' => 'decoy::tasks@execute', 'as' => 'decoy::tasks@execute'));

// View worker status
Route::get('(:bundle)/workers', array('uses' => 'decoy::workers@index', 'as' => 'decoy::workers'));
Route::get('(:bundle)/workers/tail/(:any).txt', array('uses' => 'decoy::workers@tail', 'as' => 'decoy::workers@tail'));
