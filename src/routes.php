<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

// View worker status
Route::get('(:bundle)/workers', array('uses' => 'decoy::workers@index', 'as' => 'decoy::workers'));
Route::get('(:bundle)/workers/tail/(:any).txt', array('uses' => 'decoy::workers@tail', 'as' => 'decoy::workers@tail'));
