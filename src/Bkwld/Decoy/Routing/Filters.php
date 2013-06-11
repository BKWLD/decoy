<?php namespace Bkwld\Decoy\Routing;

// Dependencies
use Route;
use Log;

/**
 * Route filters for Decoy
 */
class Filters {
	
	// Properties
	private $dir; // The path "directory" of the admin.  I.e. "admin"
	
	/**
	 * Constructor
	 * @param string $dir The path "directory" of the admin.  I.e. "admin"
	 */
	public function __construct($dir) {
		$this->dir = $dir;
	}
	
	/**
	 * Register all filters
	 */
	public function registerAll() {
		
		Route::filter('one', function() {
			//
			Log::info('one');
		});
		
		Route::filter('two', array($this, 'saveRedirect'));

		
		Route::when('admin/*', 'one');
		Route::when('admin/*', 'two');
	}
	
	/**
	 * Handle the redirection after save that depends on what submit
	 * button the user interacte with
	 */
	public function saveRedirect() {
		Log::info('two');
	}
	
}