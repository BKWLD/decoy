<?php namespace Bkwld\Decoy\Routing;

// Dependencies
use Bkwld\Decoy\Breadcrumbs;
use Config;
use DecoyAuth;
use DecoyURL;
use HTML;
use Input;
use Redirect;
use Request;
use Route;
use Session;
use Str;
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
		
		// Access control
		Route::filter('decoy.acl', array($this, 'acl'));
		Route::when($this->dir.'/*', 'decoy.acl');
		
		// Save redirect
		Route::filter('decoy.saveRedirect', array($this, 'saveRedirect'));
		Route::when($this->dir.'/*', 'decoy.saveRedirect');
		
		// Redirect old edit links.  Route::when() does not support true regular
		// expressions, thus had to catch all
		Route::filter('decoy.editRedirect', array($this, 'editRedirect'));
		Route::when($this->dir.'/*', 'decoy.editRedirect', array('get'));
	}
	
	/**
	 * Force users to login to the admin
	 */
	public function acl() {
		
		// Do nothing if the current path contains any of the whitelisted urls
		$path = '/'.Request::path();
		if ($path === parse_url(route('decoy'), PHP_URL_PATH)                  // Login
			|| $path === parse_url(route('decoy\account@forgot'), PHP_URL_PATH)  // Forgot
			|| Str::startsWith($path, '/'.$this->dir.'/reset/')) return;         // Reset
		
		// Everything else in admin requires a logged in user.  So redirect
		// to login and pass along the current url so we can take the user there.
		if (!DecoyAuth::check()) return App::make('decoy.acl_fail');
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
			Session::flash('save_redirect', DecoyURL::relative('create'));
		}
	}
	
	/**
	 * Redirect old style edit links to the new /edit route
	 */
	public function editRedirect() {
		$url = Request::url();
		if (preg_match('#/\d+$#', $url)) return Redirect::to($url.'/edit');
	}
	
}