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
		
		// Filters is a dependency of router and it's used elsewhere
		$dir = Config::get('decoy::dir');
		$filters = new Routing\Filters($dir);
		$this->app->instance('decoy.filters', $filters);

		// Register the routes AFTER all the app routes using the "before" register
		$router = new Routing\Router($dir, $filters);
		$this->app->instance('decoy.router', $router);
		$this->app->before(array($router, 'registerAll'));

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
		require_once(__DIR__.'/../../composers/shared.list._control_group.php');
		
		// Change Former's required field HTML
		Config::set('former::required_text', ' <i class="icon-exclamation-sign js-tooltip required" title="Required field"></i>');

		// Tell Former to include unchecked checkboxes in the post
		Config::set('former::push_checkboxes', true);
			
		// Tell Laravel where to find the views that were pushed out with the config files
		$this->app->make('view')->addNamespace('decoy_published', app_path().'/config/packages/bkwld/decoy/views');
		
		// Listen for CSRF errors and kick the user back to the login screen (rather than throw a 500 page)
		$this->app->error(function(\Illuminate\Session\TokenMismatchException $e) {
			return App::make('decoy.acl_fail');
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
		
		// Register external packages
		$this->registerPackages();
		
		// Register HTML view helpers as "Decoy".  So they get invoked like: `Decoy::title()`
		$this->app->singleton('decoy', function($app) {
			return new Helpers;
		});
		
		// Wildcard router
		$this->app->singleton('decoy.wildcard', function($app) {
			$request = $app->make('request');
			return new Routing\Wildcard(
				Config::get('decoy::dir'),
				$request->getMethod(), 
				$request->path()
			);
		});
		
		// Return a redirect response with extra stuff
		$this->app->singleton('decoy.acl_fail', function($app) {
			return $app->make('redirect')->to($app->make('decoy.auth')->deniedUrl())
				->with('login_error', 'You must login first.')
				->with('login_redirect', $app->make('request')->fullUrl());
		});
		
		// Register URL Generators as "DecoyURL".
		$this->app->singleton('decoy.url', function($app) {
			return new Routing\UrlGenerator($app->make('request')->path());
		});
		
		// Build the auth instance
		$this->app->singleton('decoy.auth', function($app) {
			$auth_class = $app->make('config')->get('decoy::auth_class');
			if (!class_exists($auth_class)) throw new Exception('Auth class does not exist: '.$auth_class);
			$instance = new $auth_class;
			if (!is_a($instance, 'Bkwld\Decoy\Auth\AuthInterface')) throw new Exception('Auth class does not implement Auth\AuthInterface:'.$auth_class);
			return $instance;
		});
		
		// Register commands
		$this->app->singleton('command.decoy.generate', function($app) { return new Commands\Generate; });
		$this->commands(array('command.decoy.generate'));

		// Simple singletons
		$this->app->singleton('decoy.slug', function($app) { return new Input\Slug; });
		
	}
	
	/**
	 * Register external dependencies
	 */
	private function registerPackages() {
		
		// Former
		AliasLoader::getInstance()->alias('Former', 'Former\Facades\Former');
		$this->app->register('Former\FormerServiceProvider');
		
		// Sentry
		AliasLoader::getInstance()->alias('Sentry', 'Cartalyst\Sentry\Facades\Laravel\Sentry');
		$this->app->register('Cartalyst\Sentry\SentryServiceProvider');
		
		// Croppa
		AliasLoader::getInstance()->alias('Croppa', 'Bkwld\Croppa\Facade');
		$this->app->register('Bkwld\Croppa\ServiceProvider');
		
		// BKWLD PHP Library
		$this->app->register('Bkwld\Library\LibraryServiceProvider');
		
	}
	
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return array('decoy', 
			'decoy.url', 
			'decoy.router', 
			'decoy.slug', 
			'decoy.wildcard', 
			'decoy.acl_fail', 
			'decoy.auth',
			'decoy.filters',
			'command.decoy.generate',
		);
	}

}