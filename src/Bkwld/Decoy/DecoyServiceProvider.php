<?php namespace Bkwld\Decoy;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\ServiceProvider;
use \Config;

class DecoyServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('bkwld/decoy');
		
		// Load the other packages that we depend on.  Doing it here so the developer
		// doesn't need to add them to the app config
		// THIS DIDN'T WORK, SEE https://github.com/BKWLD/decoy/issues/67
		// $services = new ProviderRepository(new Filesystem, Config::get('app.manifest'));
		// $services->load($this->app, array(
		// 	'Former\FormerServiceProvider',
		// ));
		
		// Alias the auth class that is defined in the config for easier referencing.
		// Call it "Decoy_Auth"
		if (!class_exists('Decoy_Auth')) {
			$auth_class = Config::get('decoy::auth_class');
			if (!class_exists($auth_class)) throw new Exception('Auth class does not exist: '.$auth_class);
			class_alias($auth_class, 'Decoy_Auth', true);
			if (!is_a(new \Decoy_Auth, 'Bkwld\Decoy\Auth\AuthInterface')) throw new Exception('Auth class does not implement Auth\AuthInterface:'.$auth_class);
		}
		
		// Load HTML helpers
		require_once(__DIR__.'/../../helpers.php');
		
		// Register the routes
		require_once(__DIR__.'/../../routes.php');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}