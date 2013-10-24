<?php namespace Bkwld\Decoy;

use App;
use Config;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\ServiceProvider;
use Former;

class DecoyServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {
		$this->package('bkwld/decoy');

		// Define constants that Decoy uses
		if (!defined('FORMAT_DATE'))     define('FORMAT_DATE', 'm/d/y');
		if (!defined('FORMAT_DATETIME')) define('FORMAT_DATETIME', 'm/d/y g:i a T');
		if (!defined('FORMAT_TIME'))     define('FORMAT_TIME', 'g:i a T');		
		
		// Alias the auth class that is defined in the config for easier referencing.
		// Call it "DecoyAuth"
		if (!class_exists('DecoyAuth')) {
			$auth_class = Config::get('decoy::auth_class');
			if (!class_exists($auth_class)) throw new Exception('Auth class does not exist: '.$auth_class);
			class_alias($auth_class, 'DecoyAuth', true);
			if (!is_a(new \DecoyAuth, 'Bkwld\Decoy\Auth\AuthInterface')) throw new Exception('Auth class does not implement Auth\AuthInterface:'.$auth_class);
		}
		
		// Register the routes
		$dir = Config::get('decoy::dir');
		$request = $this->app->make('request');
		$router = new Routing\Router($dir, $request);
		$router->registerAll();
		$this->app->instance('decoy.router', $router);
		
		// Register URL Generators as "DecoyURL".
		$this->app->singleton('decoy.url', function($app) use ($request) {
			return new Routing\UrlGenerator($request->path());
		});
		
		// Do bootstrapping that only matters if user has requested an admin URL
		if ($this->app['request']->is($dir.'*')) $this->usingAdmin();
		
	}
	
	/**
	 * Things that happen only if the request is for the admin
	 */
	public function usingAdmin() {
		
		// Load HTML helpers ** Deprecated **
		require_once(__DIR__.'/../../helpers.php');
		
		// Load all the composers
		require_once(__DIR__.'/../../composers/layouts._breadcrumbs.php');
		require_once(__DIR__.'/../../composers/layouts._nav.php');
		require_once(__DIR__.'/../../composers/shared.list._standard.php');
		
		// Change former's required field HTML
		Config::set('former::required_text', ' <i class="icon-exclamation-sign js-tooltip required" title="Required field"></i>');

		// Tell former to include unchecked checkboxes in the post
		Config::set('former::push_checkboxes', true);
		
		// Tell Laravel where to find the views that were pushed out with the config files
		App::make('view')->addNamespace('decoy_published', app_path().'/config/packages/bkwld/decoy/views');
		
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
		
		// Former
		AliasLoader::getInstance()->alias('Former', 'Former\Facades\Former');
		$this->app->register('Former\FormerServiceProvider');
		
		// Sentry
		AliasLoader::getInstance()->alias('Sentry', 'Cartalyst\Sentry\Facades\Laravel\Sentry');
		$this->app->register('Cartalyst\Sentry\SentryServiceProvider');
		
		// Croppa
		AliasLoader::getInstance()->alias('Cropa', 'Bkwld\Croppa\Facade');
		$this->app->register('Bkwld\Croppa\ServiceProvider');
		
		// BKWLD PHP Library
		$this->app->register('Bkwld\Library\LibraryServiceProvider');
		
		// Register HTML view helpers as "Decoy".  So they get invoked like: `Decoy::title()`
		$this->app->singleton('decoy', function($app) {
			return new Helpers;
		});
		
		// Simple singletons
		$this->app->singleton('decoy.slug', function($app) { return new Input\Slug; });

	}
	
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return array('decoy', 'decoy.url', 'decoy.router', 'decoy.slug');
	}

}