<?php namespace Bkwld\Decoy\Routing;

// Dependencies
use App;
use Bkwld\Decoy\Breadcrumbs;
use Config;
use Decoy;
use DecoyURL;
use HTML;
use Input;
use Redirect;
use Request;
use Route;
use Session;
use Str;
use URL;
use View;

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
	 * Register all filters during the `before` callback
	 *
	 * @param Illuminate\Http\Request $request 
	 */
	public function onBefore($request) {

		// Add Decoy's frontend tools
		Route::after([$this, 'frontendTools']);

		// Dont' register anything more if we're not in the admin.
		if (!Decoy::handling()) return;

		// Filters are added during a "before" handler via the Decoy service
		// provider, so this can just be run directly.
		$this->csrf();
		
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

		// Tell IE that we're compatible so it doesn't show the compatbility checkbox
		Route::after(function($request, $response) {
			$response->header('X-UA-Compatible', 'IE=Edge');
		});
	}

	/**
	 * Add markup needed for Decoy's frontend tools
	 *
	 * @param $request Illuminate\Http\Request
	 * @param $response Illuminate\Http\Response
	 */
	public function frontendTools($request, $response) {
		if (app('decoy.auth')->check() // Require an authed admin
			&& !Decoy::handling() // Don't apply to the backend
			&& ($content = $response->getContent()) // Get the whole response HTML
			&& is_string($content)) { // Double check it's a string
			
			// Add the Decoy Frontend markup to the page right before the closing body tag
			$response->setContent(
				str_replace('</body>', View::make('decoy::frontend._embed').'</body>', $content)
			);
		}
	}
	
	/**
	 * Force users to login to the admin
	 */
	public function acl() {
		
		// Do nothing if the current path contains any of the whitelisted urls
		if ($this->isPublic()) return;
		
		// Everything else in admin requires a logged in user.  So redirect
		// to login and pass along the current url so we can take the user there.
		if (!App::make('decoy.auth')->check()) return App::make('decoy.acl_fail');

		// If permissions were defined, see if the user has permission for the current action
		if (Config::has('permissions')) {
			$wildcard = app('decoy.wildcard');
			if (!app('decoy.auth')->can(
				$this->mapActionToPermission($wildcard->detectAction()), 
				$wildcard->detectControllerName())) {

				// If they don't throw the appropriate arror
				return App::abort(401);
			}
		}
	}
	
	/**
	 * Return boolean if the current URL is a public one.  Meaning, ACL is not enforced
	 * @return boolean
	 */
	public function isPublic() {
		$path = '/'.Request::path();
		return $path === parse_url(route('decoy'), PHP_URL_PATH)               // Login
			|| $path === parse_url(route('decoy::account@forgot'), PHP_URL_PATH)  // Forgot
			|| Str::startsWith($path, '/'.$this->dir.'/reset/')                  // Reset
			|| Route::is('decoy::encode@notify')                                  // Notification handler from encoder
		;
	}

	/**
	 * Map the actions from the wildcard router into the smaller set supported by
	 * the Decoy permissions system
	 */
	private function mapActionToPermission($action) {
		switch($action) {
			case 'new':
			case 'store': return 'create';
			case 'edit':
			case 'index':
			case 'indexChild': return 'read';
			default: return $action;
		}
	}

	/**
	 * Handle the redirection after save that depends on what submit
	 * button the user interacte with
	 */
	public function saveRedirect() {
		
		// Handle a redirect request.  But only if there were no validation errors
		if (Session::has('save_redirect') && !Session::has('errors')) {
			Session::keep(['success', 'errors']);
			return Redirect::to(Session::get('save_redirect'));
		}
		
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

	/**
	 * Apply CSRF
	 */
	public function csrf() {

		// Routes to ignore.  Note, for some reason the 
		if (Request::is(Route::getRoutes()->getByName('decoy::encode@notify')->uri())) return;

		// Apply it
		return \Bkwld\Library\Laravel\Filters::csrf();

	}
	
}