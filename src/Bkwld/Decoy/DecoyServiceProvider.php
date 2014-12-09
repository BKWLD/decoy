<?php namespace Bkwld\Decoy;

use App;
use Bkwld\Decoy\Fields\Former\MethodDispatcher;
use Config;
use Former\Former;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Yaml\Parser;

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
		$dir = Config::get('decoy::core.dir');
		$filters = new Routing\Filters($dir);
		$this->app->instance('decoy.filters', $filters);

		// Register the routes AFTER all the app routes using the "before" register.  Unless
		// the app is running via the CLI where we want the routes reigsterd for URL generation.
		$router = new Routing\Router($dir, $filters);
		$this->app->instance('decoy.router', $router);
		if (App::runningInConsole()) $router->registerAll();
		else $this->app->before(array($router, 'registerAll'));

		// Do bootstrapping that only matters if user has requested an admin URL
		if ($this->app['decoy']->handling()) $this->usingAdmin();
		
	}
	
	/**
	 * Things that happen only if the request is for the admin
	 */
	public function usingAdmin() {
		
		// Load all the composers
		require_once(__DIR__.'/../../composers/layouts._breadcrumbs.php');
		require_once(__DIR__.'/../../composers/layouts._nav.php');
		require_once(__DIR__.'/../../composers/shared.list._search.php');

		// Use Bootstrap 3
		Config::set('former::framework', 'TwitterBootstrap3');

		// Reduce the horizontal form's label width
		Config::set('former::TwitterBootstrap3.labelWidths', []);

		// Change Former's required field HTML
		Config::set('former::required_text', ' <span class="glyphicon glyphicon-exclamation-sign js-tooltip required" title="Required field"></span>');

		// Add Decoy's custom Fields to Former so they can be invoked using the "Former::"
		// namespace and so we can take advantage of sublassing Former's Field class.
		$this->app->make('former.dispatcher')->addRepository('Bkwld\Decoy\Fields\\');

		// Listen for CSRF errors and kick the user back to the login screen (rather than throw a 500 page)
		$this->app->error(function(\Illuminate\Session\TokenMismatchException $e) {
			return App::make('decoy.acl_fail');
		});

		// Use the Decoy paginator
		Config::set('view.pagination', 'decoy::shared.list._paginator');
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
				Config::get('decoy::core.dir'),
				$request->getMethod(), 
				$request->path()
			);
		});
		
		// Return a redirect response with extra stuff
		$this->app->singleton('decoy.acl_fail', function($app) {
			return $app->make('redirect')->to($app->make('decoy.auth')->deniedUrl())
				->withErrors([ 'error message' => 'You must login first'])
				->with('login_redirect', $app->make('request')->fullUrl());
		});
		
		// Register URL Generators as "DecoyURL".
		$this->app->singleton('decoy.url', function($app) {
			return new Routing\UrlGenerator($app->make('request')->path());
		});
		
		// Build the auth instance
		$this->app->singleton('decoy.auth', function($app) {

			// Build an instance of the specified auth class if it's a valid class path
			$auth_class = $app->make('config')->get('decoy::core.auth_class');
			if (!class_exists($auth_class)) throw new Exception('Auth class does not exist: '.$auth_class);
			$instance = new $auth_class;
			if (!is_a($instance, 'Bkwld\Decoy\Auth\AuthInterface')) throw new Exception('Auth class does not implement Auth\AuthInterface:'.$auth_class);

			// If using Sentry, apply customizations.  Do this here so that requests that
			// aren't handled by Decoy (like the requireDecoyAuthUntilLive() one) will benefit
			// from the customizations.
			if ($auth_class == '\Bkwld\Decoy\Auth\Sentry') {

				// Disable the checkPersistCode() function when not on a live/prod site
				$app->make('config')->set(
					'cartalyst/sentry::users.model', 
					'Bkwld\Decoy\Auth\SentryUser'
				);
			}
			
			// Return the auth class instance
			return $instance;
		});
		
		// Build the Elements collection
		$this->app->singleton('decoy.elements', function($app) {
			return new Collections\Elements(
				new Parser, 
				new Models\Element, 
				$this->app->make('cache')->driver()
			);
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

		// HAML
		$this->app->register('Bkwld\LaravelHaml\ServiceProvider');
		
	}
	
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return array(
			'command.decoy.generate',
			'decoy', 
			'decoy.acl_fail', 
			'decoy.auth',
			'decoy.elements',
			'decoy.filters',
			'decoy.router', 
			'decoy.slug', 
			'decoy.url', 
			'decoy.wildcard', 
		);
	}

}