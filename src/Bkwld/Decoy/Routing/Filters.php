<?php namespace Bkwld\Decoy\Routing;

// Dependencies
use Agent; // Laravel-Agent package
use App;
use Bkwld\Decoy\Breadcrumbs;
use Config;
use Decoy;
use DecoyURL;
use HTML;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\MessageBag;
use Input;
use Redirect;
use Request;
use Route;
use Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Support\Str;
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

		// Add Decoy's frontend tools if show_frontend_tools is set in the site config
		if (Config::get('decoy.site.show_frontend_tools')) Route::after([$this, 'frontendTools']);

		// Dont' register anything more if we're not in the admin.
		if (!Decoy::handling()) return;

		// Filters are added during a "before" handler via the Decoy service
		// provider, so this can just be run directly.
		$this->csrf();
		$this->supportedBrowsers();

		// Tell IE that we're compatible so it doesn't show the compatbility checkbox
		Route::after(function($request, $response) {
			$response->header('X-UA-Compatible', 'IE=Edge');
		});
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

	/**
	 * Enforce supported browsers restrictions
	 */
	public function supportedBrowsers() {

		// No Android default browser
		if (Agent::isAndroidOS() && !Agent::isChrome()) $this->addError('Your web browser is not 
			supported, please install <a href="https://play.google.com/store/apps/details?id=com.android.chrome" 
			class="alert-link">Chrome for Android</a>.');

		// No IE < 9
		else if (Agent::isIE() && Agent::version('IE', 'float') < 9) $this->addError('Your web browser is not 
			supported, please <a href="http://whatbrowser.org/" class="alert-link">upgrade</a>.');
	}

	/**
	 * Add an error to the session view errors
	 *
	 * @param string $message 
	 */
	protected function addError($message) {
		View::shared('errors')->put('default', new MessageBag([
			'error message' => $message,
		]));
	}
	
}