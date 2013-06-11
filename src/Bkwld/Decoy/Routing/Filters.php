<?php namespace Bkwld\Decoy\Routing;

// Dependencies
use Bkwld\Decoy\Breadcrumbs;
use HTML;
use Input;
use Redirect;
use Route;
use Session;
use URL;

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
		Route::filter('decoy.saveRedirect', array($this, 'saveRedirect'));
		Route::when('admin/*', 'decoy.saveRedirect');
	}
	
	/**
	 * Handle the redirection after save that depends on what submit
	 * button the user interacte with
	 */
	public function saveRedirect() {
		
		// Handle a redirect request
		if (Session::has('save_redirect')) return Redirect::to(Session::get('save_redirect'));
		
		// Only act on save values of 'back' or 'new'
		if (!Input::has('_save') || Input::get('_save') == 'save') return;
		
		// Go back to the listing
		if (Input::get('_save') == 'back') {
			Session::flash('save_redirect', Breadcrumbs::smartBack());
		}
		
		// Go to new form by stripping the last segment from the URL
		if (Input::get('_save') == 'new') {
			Session::flash('save_redirect', HTML::relative('create'));
		}		
	}
	
}